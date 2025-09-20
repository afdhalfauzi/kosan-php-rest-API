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
    handleDelete($pdo);
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

  $sql = "SELECT $select FROM smarthomes";
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
  $sql = "INSERT INTO smarthomes (deviceId, roomId, deviceType, status, lastUpdated) VALUES (:deviceId, :roomId, :deviceType, :status, :lastUpdated)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['deviceId' => $input['deviceId'], 'roomId' => $input['roomId'], 'deviceType' => $input['deviceType'], 'status' => $input['status'], 'lastUpdated' => $input['lastUpdated']]);
  echo json_encode(['message' => 'Smarthome added succesfully']);
}

function handlePut($pdo, $input)
{
  // $sql = "UPDATE smarthomes SET roomId=:roomId, deviceType=:deviceType, status=:status, lastUpdated=:lastUpdated WHERE deviceId=:deviceId";
  // $stmt = $pdo->prepare($sql);
  // $stmt->execute(['deviceId' => $input['deviceId'], 'roomId' => $input['roomId'], 'deviceType' => $input['deviceType'], 'status' => $input['status'], 'lastUpdated' => $input['lastUpdated']]);
  // echo json_encode(['message' => 'Smarthome updated succesfully']);
  if (!isset($input['deviceId'])) {
    echo json_encode(['message' => 'deviceId is required']);
    return;
  }
  $cols = ['roomId', 'deviceType', 'status', 'lastUpdated'];
  $fields = [];
  $params = [];

  foreach ($cols as $col) {
    if (isset($input[$col])) {
      $fields[] = "$col = :$col";
      $params[":$col"] = $input[$col];
    }
  }

  if ($fields) {
    $sql = "UPDATE smarthomes SET " . implode(", ", $fields) . " WHERE deviceId = :deviceId";
    $params[":deviceId"] = $input['deviceId'];

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
      echo json_encode(["message" => "Device updated successfully"]);
    } else {
      echo json_encode(["error" => $stmt->errorInfo()]);
    }
  } else {
    echo json_encode(["error" => "No fields to update"]);
  }
}

function handleDelete($pdo)
{
  $sql = "DELETE FROM smarthomes WHERE deviceId = :deviceId";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['deviceId' => $_GET['deviceId']]);
  echo json_encode(['message' => "Smarthome deleted succesfully"]);
}
?>