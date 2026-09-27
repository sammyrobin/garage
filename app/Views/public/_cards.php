<?php /** @var array $cars */ ?>
<?php foreach ($cars as $car): ?>
<?= Garage\Core\View::partial('public/_card', ['car' => $car]) ?>
<?php endforeach; ?>
