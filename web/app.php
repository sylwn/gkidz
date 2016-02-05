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
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->get('/', function() use ($app) {
    return $app['twig']->render('index.html.twig');
})
->bind('display');;

$app->get('/list', function() use($app) {
    $texts = $app['db']->fetchAll('select * from text where status = \'pending\'');
    return $app['twig']->render('list.html.twig', array('texts' => $texts));
})
->bind('list');;

$app->get('/question', function() use($app) {
    $form = $app['form.factory']->createBuilder('form')
        ->add('text', 'textarea')
        ->getForm();

    return $app['twig']->render('question.html.twig', array('form' => $form->createView()));
})
->bind('question');;

$app->post('/question', function(Request $request) use($app) {
    $form = $app['form.factory']->createBuilder('form')
        ->add('text')
        ->getForm();

    $form->handleRequest($request);
    if ($form->isValid()) {
        $data = $form->getData();
        $data['status'] = 'pending';

        $app['db']->insert('text', $data);
        return $app->redirect('/question');
    }

    return $app['twig']->render('question.html.twig', array('form' => $form->createView()));
})
->bind('post.question');

$app->get('/archive', function(Request $request) use($app) {
    $textId = $request->get('id');
    $app['db']->update('text', array('status' => 'archived'), array('id' => $textId));
    return $app->redirect($app['url_generator']->generate('list'));
})
->bind('archive');

$app->get('/accept', function(Request $request) use($app) {
    $textId = $request->get('id');
    $app['db']->update('text', array('status' => 'accepted'), array('id' => $textId));
    return $app->redirect($app['url_generator']->generate('list'));
})
->bind('accept');

$app->get('/refuse', function(Request $request) use($app) {
    $textId = $request->get('id');
    $app['db']->update('text', array('status' => 'refused'), array('id' => $textId));
    return $app->redirect($app['url_generator']->generate('list'));
})
->bind('refuse');


$app->run();
