<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = true;

//Register database
if(!file_exists($dbConfigFile = __DIR__ . "/../app/config/database.json")){
    echo 'Cannot find database config file. Please create the file.';
    exit;
}
$dbConfig = json_decode(file_get_contents($dbConfigFile), true);

$app->register(new Silex\Provider\DoctrineServiceProvider(), array($dbConfig));
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/Ressource/views',
));
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider());

$app->get('/', function(Request $request) use ($app) {
    $form = $app['form.factory']->createBuilder('form')
        ->add('question', 'textarea')
        ->getForm();

    return $app['twig']->render('index.html.twig', array('form' => $form->createView()));
});

$app->post('/', function(Request $request) use ($app) {
    $form = $app['form.factory']->createBuilder('form')
        ->add('question')
        ->getForm();

    $form->handleRequest($request);
    if ($form->isValid()) {
        $app['db']->insert('text',$form->getData());
        return $this->redirect('/');
    }

    return $app['twig']->render('index.twig', array('form' => $form->createView()));
});

$app->get('/question/list', function() use($app) {
  return $app['twig']->render('list.html.twig');
});

$app->get('/question/display', function() use($app) {
  return $app['twig']->render('display.html.twig');
});

$app->get('/question/create', function() use($app) {
  return $app['twig']->render('display.html.twig');
});


$app->run();
