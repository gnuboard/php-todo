<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/vendor/autoload.php';

// 데이터베이스 연결
$db = new PDO('mysql:host=localhost;dbname=todo', 'todo', 'todo');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$app = AppFactory::create();

// 오류 미들웨어 추가
$app->addErrorMiddleware(true, true, true);

// Twig 설정
$twig = Twig::create('templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

// 모든 할 일 목록 조회 (Read)
$app->get('/', function (Request $request, Response $response) use ($db) {
    $stmt = $db->query("SELECT * FROM todos ORDER BY id DESC");
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $view = Twig::fromRequest($request);
    return $view->render($response, 'index.twig', ['todos' => $todos]);
});

// 새 할 일 추가 (Create)
$app->post('/add', function (Request $request, Response $response) use ($db) {
    $data = $request->getParsedBody();
    $task = $data['todo'];
    
    $stmt = $db->prepare("INSERT INTO todos (task) VALUES (:task)");
    $stmt->execute(['task' => $task]);
    
    return $response->withHeader('Location', '/')->withStatus(302);
});

// 할 일 수정 폼 (Update - 폼)
$app->get('/edit/{id}', function (Request $request, Response $response, $args) use ($db) {
    $id = $args['id'];
    
    $stmt = $db->prepare("SELECT * FROM todos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $todo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $view = Twig::fromRequest($request);
    return $view->render($response, 'edit.twig', ['todo' => $todo]);
});

// 할 일 수정 처리 (Update - 처리)
$app->post('/edit/{id}', function (Request $request, Response $response, $args) use ($db) {
    $id = $args['id'];
    $data = $request->getParsedBody();
    $task = $data['todo'];
    
    $stmt = $db->prepare("UPDATE todos SET task = :task WHERE id = :id");
    $stmt->execute(['task' => $task, 'id' => $id]);
    
    return $response->withHeader('Location', '/')->withStatus(302);
});

// 할 일 삭제 (Delete)
$app->get('/delete/{id}', function (Request $request, Response $response, $args) use ($db) {
    $id = $args['id'];
    
    $stmt = $db->prepare("DELETE FROM todos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    return $response->withHeader('Location', '/')->withStatus(302);
});

// 할 일 완료/미완료 토글
$app->get('/toggle/{id}', function (Request $request, Response $response, $args) use ($db) {
    $id = $args['id'];
    
    $stmt = $db->prepare("UPDATE todos SET completed = NOT completed WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    return $response->withHeader('Location', '/')->withStatus(302);
});

$app->run();
