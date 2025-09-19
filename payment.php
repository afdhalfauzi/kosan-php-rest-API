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

  $sql = "SELECT $select FROM payments";
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
  $sql = "INSERT INTO payments (paymentId, amount, contractId, paymentDate, method, status, receiptUrl, createdAt) VALUES (:paymentId, :amount, :contractId, :paymentDate, :method, :status, :receiptUrl, :createdAt)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['paymentId' => $input['paymentId'], 'contractId' => $input['contractId'], 'amount' => $input['amount'], 'paymentDate' => $input['paymentDate'], 'method' => $input['method'], 'status' => $input['status'], 'receiptUrl' => $input['receiptUrl'], 'createdAt' => $input['createdAt']]);
  echo json_encode(['message' => 'Payment added succesfully']);
}

function handlePut($pdo, $input)
{
  // $sql = "UPDATE payments SET amount=:amount, contractId=:contractId, paymentDate=:paymentDate, method=:method, status=:status, receiptUrl=:receiptUrl, createdAt=:createdAt WHERE paymentId=:paymentId";
  // $stmt = $pdo->prepare($sql);
  // $stmt->execute(['paymentId' => $input['paymentId'], 'contractId' => $input['contractId'], 'amount' => $input['amount'], 'paymentDate' => $input['paymentDate'], 'method' => $input['method'], 'status' => $input['status'], 'receiptUrl' => $input['receiptUrl'], 'createdAt' => $input['createdAt']]);
  // echo json_encode(['message' => 'Payment updated succesfully']);
  if (!isset($input['paymentId'])) {
    echo json_encode(['message' => 'paymentId is required']);
    return;
  }
  $cols = ['contractId', 'amount', 'paymentDate', 'method', 'status', 'receiptUrl', 'createdAt'];
  $fields = [];
  $params = [];

  foreach ($cols as $col) {
    if (isset($input[$col])) {
      $fields[] = "$col = :$col";
      $params[":$col"] = $input[$col];
    }
  }

  if ($fields) {
    $sql = "UPDATE payments SET " . implode(", ", $fields) . " WHERE paymentId = :paymentId";
    $params[":paymentId"] = $input['paymentId'];

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
      echo json_encode(["message" => "Payment updated successfully"]);
    } else {
      echo json_encode(["error" => $stmt->errorInfo()]);
    }
  } else {
    echo json_encode(["error" => "No fields to update"]);
  }
}

function handleDelete($pdo, $input)
{
  $sql = "DELETE FROM payments WHERE paymentId = :paymentId";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['paymentId' => $input['paymentId']]);
  echo json_encode(["message" => "Payment deleted succesfully"]);
}
?>