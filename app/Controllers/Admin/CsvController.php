<?php

declare(strict_types=1);

namespace Garage\Controllers\Admin;

use Garage\Core\Logger;
use Garage\Core\Request;
use Garage\Core\Response;
use Garage\Core\Session;
use Garage\Services\CsvService;

final class CsvController extends AdminController
{
    public function index(Request $request): Response
    {
        // Row errors from the last failed import are shown once.
        $report = Session::get('_csv_report');
        Session::forget('_csv_report');

        return $this->adminView('admin/csv/index', [
            'title' => t('admin.csv'),
            'section' => 'csv',
            'report' => $report,
        ]);
    }

    /** Export includes the private cost, so it needs the owner too. */
    public function export(Request $request): Response
    {
        if ($denied = $this->authorizeWrite($request, '/admin/csv')) {
            return $denied;
        }

        return (new Response(CsvService::export(true)))
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="garage-' . date('Y-m-d') . '.csv"')
            ->noIndex();
    }

    public function import(Request $request): Response
    {
        if ($denied = $this->authorizeWrite($request, '/admin/csv')) {
            return $denied;
        }

        $file = $request->file('csv');
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
            Session::flash('error', t('csv.error.upload'));
            return Response::redirect(url('/admin/csv'));
        }
        if ((int) $file['size'] > CsvService::MAX_BYTES) {
            Session::flash('error', t('csv.error.too_big'));
            return Response::redirect(url('/admin/csv'));
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        if (!in_array($mime, ['text/plain', 'text/csv', 'application/csv', 'text/x-csv', 'application/vnd.ms-excel'], true)) {
            Session::flash('error', t('csv.error.type'));
            return Response::redirect(url('/admin/csv'));
        }

        $result = CsvService::import((string) $file['tmp_name']);
        if ($result['ok']) {
            Logger::info('CSV imported', ['created' => $result['created'], 'updated' => $result['updated']]);
            Session::flash('success', t('csv.done', ['created' => (string) $result['created'], 'updated' => (string) $result['updated']]));
        } else {
            Session::flash('error', t('csv.failed'));
            Session::set('_csv_report', $result['errors']);
        }

        return Response::redirect(url('/admin/csv'));
    }
}
