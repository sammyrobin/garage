<?php

declare(strict_types=1);

namespace Garage\Controllers\Admin;

use Garage\Core\Auth;
use Garage\Core\Deferred;
use Garage\Core\Logger;
use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Core\Session;
use Garage\Models\Brand;
use Garage\Models\Car;
use Garage\Models\CarPhoto;
use Garage\Models\Series;
use Garage\Services\CarInput;
use Garage\Services\CarService;
use Garage\Services\NotificationService;
use Garage\Support\Str;

final class CarController extends AdminController
{
    private const PER_PAGE = 24;

    public function index(Request $request): Response
    {
        $search = Str::cut(Str::clean($request->query('q', '')), 80);
        $page = max(1, (int) $request->query('page', 1));
        $result = Car::paginate($search, $page, self::PER_PAGE);
        $photos = CarPhoto::forCars(array_map(static fn (array $c): int => (int) $c['id'], $result['rows']), ['front']);

        return $this->adminView('admin/cars/index', [
            'title' => t('admin.cars'),
            'section' => 'cars',
            'search' => $search,
            'result' => $result,
            'photos' => $photos,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(null, [], [], 200);
    }

    public function edit(Request $request, array $params): Response
    {
        $car = ($id = $this->id($params)) ? Car::find($id) : null;

        return $car ? $this->form($car, [], [], 200) : Response::notFound();
    }

    public function store(Request $request): Response
    {
        // 1) CSRF + password/session — before touching any file.
        if ($denied = $this->authorizeWrite($request, '/admin/cars/new')) {
            return $denied;
        }

        // 2) Validate fields and every photo; nothing is written yet.
        $input = CarInput::fromArray($request->all());
        $files = $this->photoFiles($request);
        $photoErrors = CarService::validatePhotos($files);

        if (!$input->passes() || $photoErrors) {
            return $this->invalid($request, null, $input->errors, $photoErrors);
        }

        // 3) Process photos + insert in one transaction.
        $carId = CarService::create($input->data, $files);
        Logger::info('Car created', ['id' => $carId]);

        // 4) E-mail: "Save" sends now (grouped with anything pending); "Save and add another" waits.
        NotificationService::queue($carId);
        $addAnother = $request->input('action') === 'save_add';
        if (!$addAnother) {
            Deferred::add([NotificationService::class, 'flush']);
        }

        $car = Car::find($carId);
        Session::flash('success', t($addAnother ? 'car.saved_add' : 'car.saved', ['name' => $car['name']]));
        $next = $addAnother ? url('/admin/cars/new') : url('/admin/cars/' . $carId . '/edit');

        return $this->wantsJson($request)
            ? Response::json(['ok' => true, 'redirect' => $next])
            : Response::redirect($next);
    }

    public function update(Request $request, array $params): Response
    {
        $car = ($id = $this->id($params)) ? Car::find($id) : null;
        if (!$car) {
            return Response::notFound();
        }

        // Without a session the visitor never saw the private cost: an empty field keeps it.
        $hadSession = Auth::check();
        $back = '/admin/cars/' . $car['id'] . '/edit';
        if ($denied = $this->authorizeWrite($request, $back)) {
            return $denied;
        }

        $input = CarInput::fromArray($request->all(), keepCost: !$hadSession);
        $files = $this->photoFiles($request);
        $remove = array_values(array_intersect(CarPhoto::ANGLES, (array) $request->input('remove', [])));
        $photoErrors = CarService::validatePhotos($files, $remove, CarPhoto::forCar((int) $car['id']));

        if (!$input->passes() || $photoErrors) {
            return $this->invalid($request, $car, $input->errors, $photoErrors);
        }

        CarService::update((int) $car['id'], $input->data, $files, $remove);
        Session::flash('success', t('car.updated', ['name' => $input->data['name']]));
        $next = url($back);

        return $this->wantsJson($request)
            ? Response::json(['ok' => true, 'redirect' => $next])
            : Response::redirect($next);
    }

    public function destroy(Request $request, array $params): Response
    {
        $car = ($id = $this->id($params)) ? Car::find($id) : null;
        if (!$car) {
            return Response::notFound();
        }
        if ($denied = $this->authorizeWrite($request, '/admin/cars/' . $car['id'] . '/edit')) {
            return $denied;
        }

        CarService::delete((int) $car['id']);
        Session::flash('success', t('car.deleted', ['name' => $car['name']]));

        return Response::redirect(url('/admin/cars'));
    }

    /** @return array<string, ?array> angle => uploaded file */
    private function photoFiles(Request $request): array
    {
        $files = [];
        foreach (CarPhoto::ANGLES as $angle) {
            $files[$angle] = $request->file('photo_' . $angle);
        }

        return $files;
    }

    private function invalid(Request $request, ?array $car, array $fieldErrors, array $photoErrors): Response
    {
        $messages = array_map('t', $fieldErrors);
        foreach ($photoErrors as $angle => $key) {
            $messages['photo_' . $angle] = t($key);
        }

        if ($this->wantsJson($request)) {
            return Response::json(['ok' => false, 'message' => t('validation.summary'), 'errors' => $messages], 422);
        }

        return $this->form($car, $request->all(), $messages, 422);
    }

    private function form(?array $car, array $old, array $errors, int $status): Response
    {
        $isOwner = Auth::check();
        $values = $car ?? [];
        if ($car && !$isOwner) {
            $values['cost_mxn'] = null;   // private: never rendered without a session
        }
        foreach ($old as $key => $value) {
            if (is_string($value) && $key !== 'password' && $key !== '_csrf') {
                $values[$key] = $value;
            }
        }

        return $this->adminView('admin/cars/form', [
            'title' => $car ? t('car.edit_title', ['name' => $car['name']]) : t('car.new_title'),
            'section' => $car ? 'cars' : 'new',
            'car' => $car,
            'values' => $values,
            'errors' => $errors,
            'photos' => $car ? CarPhoto::forCar((int) $car['id']) : [],
            'brands' => Brand::all(),
            'seriesList' => Series::all(),
            'costMasked' => $car !== null && !$isOwner && $car['cost_mxn'] !== null,
            'openDetails' => (bool) array_intersect(array_keys($errors), ['series_id', 'real_year', 'casting_year', 'collection_number', 'color', 'rarity', 'item_condition', 'acquired_at', 'notes']),
        ], $status);
    }
}
