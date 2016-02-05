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

$app->register(new Silex\Provider\DoctrineServiceProvider(), array('db.options' => $dbConfig));
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/Ressource/views',
));
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider());

$app->get('/', function() use ($app) {
    return $app['twig']->render('index.html.twig');
});

$app->get('/list', function() use($app) {
    $texts = $app['db']->fetchAll('select * from text');
    return $app['twig']->render('list.html.twig', array('texts' => $texts));
});

$app->get('/question', function() use($app) {
    $form = $app['form.factory']->createBuilder('form')
        ->add('text', 'textarea')
        ->getForm();

    return $app['twig']->render('question.html.twig', array('form' => $form->createView()));
});

$app->post('/question', function(Request $request) use($app) {
    $form = $app['form.factory']->createBuilder('form')
        ->add('text')
        ->getForm();

    $form->handleRequest($request);
    if ($form->isValid()) {
        $app['db']->insert('text',$form->getData());
        return $app->redirect('/question');
    }

    return $app['twig']->render('question.html.twig', array('form' => $form->createView()));
});


$app->run();
