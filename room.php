<?php
require "connect.php";
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
  case 'GET':
    handleGet($pdo);
    break;
  case 'POST':
    handlePost($pdo, $input);
    break;
  case 'PUT':
    handlePut($pdo, $input);
    break;
  case 'DELETE':
    handleDelete($pdo, $input);
    break;
  default:
    echo json_encode(['message' => 'Invalid request method']);
    break;
}

function handleGet($pdo)
{
  $select = "*"; //default

  if (isset($_GET['select']) && !empty($_GET['select'])) {
    $rawFields = $_GET['select'];
    //filter
    if (preg_match('/^[a-zA-Z0-9_,]+$/', $rawFields)) {
      $select = $rawFields;
    }
  }

  $sql = "SELECT $select FROM rooms";
  $conditions = [];
  $params = [];

  foreach ($_GET as $key => $value) {
    if (in_array($key, ["table", "select"]))
      continue;
    $conditions[] = "$key = :$key";
    $params[$key] = $value;
  }

  if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
  }

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($result);
}

function handlePost($pdo, $input)
{
  $sql = "INSERT INTO rooms (roomId, roomNumber, status, price, facilities, photoUrl, createdAt) VALUES (:roomId, :roomNumber, :status, :price, :facilities, :photoUrl, :createdAt)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['roomId' => $input['roomId'], 'roomNumber' => $input['roomNumber'], 'status' => $input['status'], 'price' => $input['price'], 'facilities' => $input['facilities'], 'photoUrl' => $input['photoUrl'], 'createdAt' => $input['createdAt']]);
  echo json_encode(['message' => 'Room added succesfully']);
}

function handlePut($pdo, $input)
{
  // $sql = "UPDATE rooms SET roomNumber=:roomNumber, status=:status, price=:price, facilities=:facilities, photoUrl=:photoUrl, createdAt=:createdAt WHERE roomId=:roomId";
  // $stmt = $pdo->prepare($sql);
  // $stmt->execute(['roomId' => $input['roomId'], 'roomNumber' => $input['roomNumber'], 'status' => $input['status'], 'price' => $input['price'], 'facilities' => $input['facilities'], 'photoUrl' => $input['photoUrl'], 'createdAt' => $input['createdAt']]);
  // echo json_encode(['message' => 'Room updated succesfully']);
  if (!isset($input['roomId'])) {
    echo json_encode(['message' => 'roomId is required']);
    return;
  }
  $cols = ['roomNumber', 'status', 'price', 'facilities', 'photoUrl', 'createdAt'];
  $fields = [];
  $params = [];

  foreach ($cols as $col) {
    if (isset($input[$col])) {
      $fields[] = "$col = :$col";
      $params[":$col"] = $input[$col];
    }
  }

  if ($fields) {
    $sql = "UPDATE rooms SET " . implode(", ", $fields) . " WHERE roomId = :roomId";
    $params[":roomId"] = $input['roomId'];

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
      echo json_encode(["message" => "Room updated successfully"]);
    } else {
      echo json_encode(["error" => $stmt->errorInfo()]);
    }
  } else {
    echo json_encode(["error" => "No fields to update"]);
  }
}

function handleDelete($pdo, $input)
{
  $sql = "DELETE FROM rooms WHERE roomId = :roomId";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['roomId' => $input['roomId']]);
  echo json_encode(["message" => "Room deleted succesfully"]);
}
?>