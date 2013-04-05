<?php

require('../vendor/autoload.php');

$app = new \Slim\Slim(
	array(
		'view' => new \Slim\Extras\Views\Twig(),
    	'templates.path' => '../Views',
	)
);

$app->add(new \Slim\Middleware\SessionCookie);


$app->get('/', function () use ($app) {
	$s = new \Stagr\StagrCon;
	if($s->stagrFileExists()) {
		$data = array(
			"apps" => $s->getAppNames()
		);
		$app->render("header.php");
		$app->render("main.php", $data);
		$app->render("footer.php");
	} else {
		$app->redirect('/setup');
	}
});

$app->get('/setup', function () use ($app) {
	$s = new \Stagr\StagrCon;
	if(!$s->stagrFileExists()) {
		$app->render("header.php");
		$app->render("setup.php");
		$app->render("footer.php");
	} else {
		$app->redirect("/");
	}
});

$app->post('/setup/save', function () use ($app) {
	if ($_POST['email'] && $_POST['pubkey']) {
		$s = new \Stagr\StagrCon;
		$s->createStagrFile($_POST['email'], $_POST['pubkey']);
	} else {
		$app->redirect("/setup");
	}
});

$app->get('/apps/:name/settings', function ($name) use ($app) {
	$s = new \Stagr\StagrCon;
	if($s->stagrFileExists()) {
		$data = array(
			"app" => $s->getApp($name),
			"timezones" => $s->getTimeZones(),
			"exectimes" => $s->getExecutionTimes(),
			"memorysizes" => $s->getMemorySizes(),
			"apcsizes" => $s->getApcSizes(),
			"uploadsizes" => $s->getUploadSizes(),
			"postsizes" => $s->getPostSizes(),
			"buffersizes" => $s->getOutputBufferSizes()
		);
		$app->render("header.php");
		$app->render("appPage.php", $data);
		$app->render("footer.php");
	} else {
		$app->redirect('/setup');
	}
});

$app->post('/apps/:name/settings/save', function ($name) use ($app) {
	$s = new \Stagr\StagrCon;
	if ($s->stagrFileExists() && $s->getApp($name)) {
		
		$properties = array(
			'--timezone="' . $_POST['timezone'] . '"',
			'--exec-time="' . $_POST['exectime'] . '"',
			'--memory-limit="' . $_POST['memlimit'] . '"',
			'--apc-size="' . $_POST['apclimit'] . '"',
			'--upload-size="' . $_POST['uploadsize'] . '"',
			'--post-size="' . $_POST['postsize'] . '"',
			'--output-buffering="' . $_POST['outputsize'] . '"',
			'--doc-root="' . $_POST['docroot'] . '"',
		);

		if ($_POST['shorttags'] === "Off") {
			array_push($properties, '--disable-short-tags');
		} else {
			array_push($properties, '--enable-short-tags');
		}

		foreach ($_POST['envname'] as $key => $value) {
			if ($value) {
				array_push($properties, '--env="' . $value . '=' . $_POST['envvalue'][$key] . '"');
			}
		}
		$s->saveApp($name, $properties);
		
		$app->flash('info', 'Settings Successfully Saved');
		$app->redirect("/apps/$name/settings");
	} else {
		$app->redirect("/");
	}
});

$app->run();
