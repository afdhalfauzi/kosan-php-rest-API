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

  $sql = "SELECT $select FROM contracts";
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
  $sql = "INSERT INTO contracts (contractId, tenantId, startDate, endDate, status, createdAt) VALUES (:contractId, :tenantId, :startDate, :endDate, :status, :createdAt)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['contractId' => $input['contractId'], 'tenantId' => $input['tenantId'], 'startDate' => $input['startDate'], 'endDate' => $input['endDate'], 'status' => $input['status'], 'createdAt' => $input['createdAt']]);
  echo json_encode(['message' => 'Contract added succesfully']);
}

function handlePut($pdo, $input)
{
  // $sql = "UPDATE contracts SET tenantId=:tenantId, startDate=:startDate, endDate=:endDate, status=:status, createdAt=:createdAt WHERE contractId=:contractId";
  // $stmt = $pdo->prepare($sql);
  // $stmt->execute(['contractId' => $input['contractId'], 'tenantId' => $input['tenantId'], 'startDate' => $input['startDate'], 'endDate' => $input['endDate'], 'status' => $input['status'], 'createdAt' => $input['createdAt']]);
  // echo json_encode(['message' => 'Contract updated succesfully']);
  if (!isset($input['contractId'])) {
    echo json_encode(['message' => 'contractId is required']);
    return;
  }
  $cols = ['tenantId', 'startDate', 'endDate', 'status', 'createdAt'];
  $fields = [];
  $params = [];

  foreach ($cols as $col) {
    if (isset($input[$col])) {
      $fields[] = "$col = :$col";
      $params[":$col"] = $input[$col];
    }
  }

  if ($fields) {
        $sql = "UPDATE contracts SET " . implode(", ", $fields) . " WHERE contractId = :contractId";
        $params[":contractId"] = $input['contractId'];

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            echo json_encode(["message" => "Contract updated successfully"]);
        } else {
            echo json_encode(["error" => $stmt->errorInfo()]);
        }
    } else {
        echo json_encode(["error" => "No fields to update"]);
    }
}

function handleDelete($pdo)
{
  $sql = "DELETE FROM contracts WHERE contractId = :contractId";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['contractId' => $_GET['contractId']]);
  echo json_encode(["message" => "Contract deleted succesfully"]);
}
?>