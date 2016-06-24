<?php

use Rachet\Server\IoServer;
use FlyTextBundle\Socket\Sky;

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = true;

//Register database
if (!file_exists($dbConfigFile = __DIR__ . "/../app/config/database.json")) {
    echo 'Cannot find database config file. Please create the file.';
    exit;
}

$dbConfig = json_decode(file_get_contents($dbConfigFile), true);

$app->register(new Silex\Provider\DoctrineServiceProvider(), array('db.options' => $dbConfig));
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../src/KidBundle/Ressource/views',
));
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());

$getForm = function ($data = null) use ($app) {
    $form = $app['form.factory']->createBuilder('form', $data)
            ->add('firstname', 'text')
            ->add('lastname', 'text')
            ->add('birthday', 'date')
            ->add('gender', 'choice', array(
                'choices' => array('m' => 'Garçon', 'f' => 'Fille'),
                'expanded' => true
            ))
            ->getForm();
    
    return $form;
};

$app->get('/kidz/add', function() use ($app, $getForm) {
    $form = $getForm();
    return $app['twig']->render('add.html.twig', array('form' => $form->createView()));
})->bind('add');

$app->post('/kidz/add', function(Request $request) use ($app, $getForm) {
    $form = $getForm();
    $form->handleRequest($request);
    if ($form->isValid()) {
        $data = $form->getData();

        $data['birthday'] = $data['birthday']->format('Y-m-d');
        $app['db']->insert('kid', $data);
        $app['session']->getFlashBag()->add('message', 'Enfant rajouté.');
        return $app->redirect('/kidz/add');
    }

    return $app['twig']->render('add.html.kidztwig', array('form' => $form->createView()));
})->bind('create');

$app->get('/kidz/list', function()  use ($app, $getForm) {
    $kidz = $app['db']->fetchAll("select * from kid");
    return $app['twig']->render('list.html.twig', array('kidz' => $kidz));
})
->bind('list');

$app->get('/kidz/edit/{id}', function(Request $request)  use ($app, $getForm) {
    $id = $request->get('id');
    $sql = "SELECT * FROM kid WHERE id = ?";
    $kid = $app['db']->fetchAssoc($sql, array((int) $id));
    
    $kid['birthday'] = new \DateTime($kid['birthday']); 
    $form = $getForm($kid);
    return $app['twig']->render('edit.html.twig', array('form' => $form->createView()));
})
->bind('edit');

$app->post('/kidz/edit/{id}', function(Request $request) use ($app, $getForm)  {
    $id = $request->get('id');
    $form = $getForm();
    $form->handleRequest($request);
    if ($form->isValid()) {
        $data = $form->getData();

        $data['birthday'] = $data['birthday']->format('Y-m-d');
        $app['db']->update('kid', $data, array('id' => $id));
        $app['session']->getFlashBag()->add('message', 'Enfant rajouté.');
        
        return $app->redirect('/kidz/edit');
    }
    
    return $app['twig']->render('edit.html.twig', array('form' => $form->createView()));
})
->bind('update');

$app->get('/kidz/passeports', function()  use ($app) {
    $kidz = $app['db']->fetchAll("select * from kid");
    
    return $app['twig']->render('passeport.html.twig', array('kidz' => $kidz));
})
->bind('list');

$app->run();
