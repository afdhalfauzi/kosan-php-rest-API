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

  $sql = "SELECT $select FROM tenants";
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
  $sql = "INSERT INTO tenants (tenantId, roomId, name, email, phoneNumber, accountProvider, createdAt) VALUES (:tenantId, :roomId, :name, :email, :phoneNumber, :accountProvider, :createdAt)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['tenantId' => $input['tenantId'], 'roomId' => $input['roomId'], 'name' => $input['name'], 'email' => $input['email'], 'phoneNumber' => $input['phoneNumber'], 'accountProvider' => $input['accountProvider'], 'createdAt' => $input['createdAt']]);
  echo json_encode(['message' => 'Tenant added succesfully']);
}

function handlePut($pdo, $input)
{
  // $sql = "UPDATE tenants SET roomId=:roomId, name=:name, email=:email, phoneNumber=:phoneNumber, accountProvider=:accountProvider, createdAt=:createdAt WHERE tenantId=:tenantId";
  // $stmt = $pdo->prepare($sql);
  // $stmt->execute(['tenantId' => $input['tenantId'], 'roomId' => $input['roomId'], 'name' => $input['name'], 'email' => $input['email'], 'phoneNumber' => $input['phoneNumber'], 'accountProvider' => $input['accountProvider'], 'createdAt' => $input['createdAt']]);
  // echo json_encode(['message' => 'Tenant updated succesfully']);
  if (!isset($input['tenantId'])) {
    echo json_encode(['message' => 'tenantId is required']);
    return;
  }
  $cols = ['roomId', 'name', 'email', 'phoneNumber', 'accountProvider', 'createdAt'];
  $fields = [];
  $params = [];

  foreach ($cols as $col) {
    if (isset($input[$col])) {
      $fields[] = "$col = :$col";
      $params[":$col"] = $input[$col];
    }
  }

  if ($fields) {
    $sql = "UPDATE tenants SET " . implode(", ", $fields) . " WHERE tenantId = :tenantId";
    $params[":tenantId"] = $input['tenantId'];

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
      echo json_encode(["message" => "Tenant updated successfully"]);
    } else {
      echo json_encode(["error" => $stmt->errorInfo()]);
    }
  } else {
    echo json_encode(["error" => "No fields to update"]);
  }
}

function handleDelete($pdo, $input)
{
  $sql = "DELETE FROM tenants WHERE tenantId = :tenantId";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['tenantId' => $_GET['tenantId']]);
  echo json_encode(["message" => "Tenant deleted succesfully"]);
}
?>