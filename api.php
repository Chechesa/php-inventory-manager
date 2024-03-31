<?php

// We specify that the content will be json for the API
header('Content-Type: application/json');


// We put here the connection credentials, to avoid having down in the code
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'mysql');
define('DB_NAME', 'inventory');
//define('DB_CHARSET', 'utf8mb4');

// We create a class to manage the connection from Requests (or another tables in another exercise)
class Database {

    // Values for internal connection
    private $connection;
    private $host;
    private $user;
    private $password;
    private $database;
    
    // Initialize internal values for connection
    public function __construct($host, $user, $password, $database) {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
    }

    // Connect to the database
    public function connect() {
        $this->connection = new mysqli($this->host, $this->user, $this->password, $this->database);
        
        if ($this->connection->connect_error) {
            die("Connection error: " . $this->connection->connect_error);
        }
    }

    // We separate connection class from the API for requests (or other tables)
    // We give connection to define queries in their respective clases
    public function getConnection() {
        return $this->connection;
    }
    
    // Close the database connection
    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

}


// Request class manages all de CRUD and provides json to tables and request forms (item types) in HTML section
class Request {

    // This can be saved too in a table, but for this excersise, it will be saved here
    private $itemType = [
        'Other',
        'Office Supply',
        'Equipment',
        'Furniture',
    ];

    // Stores the connection
    private $connection;
    
    // Connects to be ready to work
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    // Retrieve table information for json from all requests, with item name and type as strings
    public function getAllRequests() {

        // Saves json ready data of requests for UI
        $data = [];

        // We have two options here:
        // a) Make a query for each item saved, normally we have to select with a JOIN but we don't have a request_item table
        // b) Get all the items to get the name
        // If we can't change data structure, we can't change data to JSON, so we choose option B

        $sqlItems = 'SELECT id, item, item_type FROM items';
        $stmtItems = $this->connection->prepare($sqlItems);
        $stmtItems->execute();
        $resultItems = $stmtItems->get_result();

        $allItems = [];

        // We build the new array base where the index is the item id
        while ($row = $resultItems->fetch_assoc()) {
            $allItems[$row['id']] = [
                'item' => $row['item'],
                'type' => $row['item_type']
            ];
        }

        // Now we get the requests
        $sql = 'SELECT * FROM requests';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        //Regex pattern to find item id and type
        $itemsRequestsPattern = '/\{(\d+),(\d+)\}/';

        while ($row = $result->fetch_assoc()) {
            // Here we clean and extract the values from the Items column in Request,
            // is not a valid JSON so we need to convert the value as we can work
            $cleanItems = str_replace(' ', '', $row['items']);
            preg_match_all($itemsRequestsPattern, $cleanItems, $matches, PREG_SET_ORDER);
            $itemsList = [];
            $itemTypes = [];
            foreach ($matches as $match) {
                $itemsList[] = $allItems[$match[1]]['item'];
                $itemTypes[] = $this->itemType[$match[2]];
            }
            // Now we give a comma separated list
            $row['items'] = implode(', ', $itemsList);
            // We take only the first item type as string
            $row['item_type'] = $itemTypes[0];
            $data[] = $row;
        }

        $data = ['data' => $data];

        $resultItems->free_result();
        $result->free_result();

        return $data;
    }

    // Retrieve request data by Id for Edit form in UI
    public function getRequestById($id = 0) {
        // Gets row from database
        $sql = 'SELECT * FROM requests WHERE req_id = ?';
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        // Get only first row (only should be one row result)
        $row = $result->fetch_assoc();

        // Regex pattern for identify all the items and item types stored in the items columns
        $itemsRequestsPattern = '/\{(\d+),(\d+)\}/';

        // Results has spaces, we need to remove it
        $cleanItems = str_replace(' ', '', $row['items']);
        // Identifies all the items and item types
        preg_match_all($itemsRequestsPattern, $cleanItems, $matches, PREG_SET_ORDER);

        // Creates an array to items that can be used in json
        $itemsList = [];
        
        // In the format, first number is item ID and second number is item type
        // Creates the item value for each item
        foreach ($matches as $match) {
            $itemsList[] = '{' . $match[1] . ',' . $match[2] . '}';
        }
        $row['items'] = $itemsList;

        $result->free_result();

        $data = ['data' => $row];
        return $data;

    }

    // Gets the items list filtered by type, to creates options in Add/Edit Form UI
    public function getItems($type = '') {
        // Variable for saving items list
        $data = [];

        $sqlItems = 'SELECT id, item, item_type FROM items';
        if (!empty($type)) {
            $sqlItems .= ' WHERE item_type = ?';
        }
        $stmtItems = $this->connection->prepare($sqlItems);
        if (!empty($type)) {
            $stmtItems->bind_param('i', $type);
        }
        
        $stmtItems->execute();
        $resultItems = $stmtItems->get_result();

        // Every result goes to $data[] array
        while ($row = $resultItems->fetch_assoc()) {
            $data[] = $row;
        }
        $data = ['data' => $data];
        return $data;
    }

    // Adds a request using data from ajax
    public function addRequest($data) {

        $sql = "INSERT INTO requests (requested_by, requested_on, ordered_on, items) VALUES (?,?,?,?)";
        $stmt = $this->connection->prepare($sql);

        // For now, we will save as requested and ordered date to today
        $today = date('Y-m-d');
        // Items array is only in {1,1} format, so we concatenate and put in [ ] string
        $itemsString = '[' . implode(',', $data['items']) . ']';
        $stmt->bind_param("ssss", $data['user'], $today, $today, $itemsString);

        // Execute prepared query and adds request
        $result = $stmt->execute();

        // After add, we update summary
        $this->updateSummary();

        return 201;

    }

    // Edits a request using the json data from ajax
    public function editRequest($id, $data) {
        // In the UI form we only have user (requested_by) and items, so, we don't update nothing more
        $sql = 'UPDATE requests SET requested_by = ?, items = ? WHERE req_id = ?';
        $stmt = $this->connection->prepare($sql);

        // We save user and items, in the same format we add
        $itemsString = '[' . implode(',', $data['items']) . ']';
        $stmt->bind_param("ssi", $data['user'], $itemsString, $data['req_id']);

        // Execute prepared query and edits the request
        $result = $stmt->execute();

        // After edit, we update summary
        $this->updateSummary();
        
        return 200;
        
    }

    // Receive the delete requests and remove the row
    public function deleteRequest($data) {
        // A simple delete quere for remove request
        $sql = "DELETE FROM requests WHERE req_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $data);

        // Execute prepared query and remove row
        $result = $stmt->execute();

        // After delete, we update summary
        $this->updateSummary();

        return $result;
    }

    // This functions builds the summary from requests, groups by user and creates the new format {item_type, [item, item, item]}
    private function updateSummary() {
        // Here are saved the items requested for the users
        $peopleRequests = [];
        // Here is saved the first request id given by the database, index will be the user
        $peopleReqId = [];
        // Here is saved the first ordered_on date given by the database, index will be the user
        $peopleOrderedOn = [];
        // Previous variabels are separated to better explaining

        // Saves the request info but ordered to INSERT in the database
        $orderedPeopleRequests = [];
        
        // Deletes all the existing rows in summary before create the updated ones
        $truncateSql = 'TRUNCATE TABLE summary';
        $truncateStmt = $this->connection->prepare($truncateSql);
        $truncateStmt->execute();

        // Receive all te request data to manipulate
        $sql = 'SELECT * FROM requests';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        //Regex pattern to find item id and type
        $itemsRequestsPattern = '/\{(\d+),(\d+)\}/';

        while ($row = $result->fetch_assoc()) {
            // Verify if any request from a user was readed before and save the req_id and ordered_on date
            if (empty($peopleReqId[strtolower($row['requested_by'])])) {
                $peopleReqId[strtolower($row['requested_by'])] = $row['req_id'];
                $peopleOrderedOn[strtolower($row['requested_by'])] = $row['ordered_on'];
            }
            // Here we clean and extract the values from the Items column in Request,
            // is not a valid JSON so we need to convert the value as we can work
            $cleanItems = str_replace(' ', '', $row['items']);
            preg_match_all($itemsRequestsPattern, $cleanItems, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $peopleRequests[strtolower($row['requested_by'])][$match[2]][] = $match[1];
            }
        }

        // Now we have a new SQL to execute for each user
        $summarySql = 'INSERT INTO summary (req_id, requested_by, ordered_on, items) VALUES (?,?,?,?)';
        $summaryStmt = $this->connection->prepare($summarySql);

        foreach ($peopleRequests as $user => $request) {
            // Format the items list by type, {type, [item1, item2, item3]}
            foreach ($request as $type => $item) {
                $orderedPeopleRequests[$user][$type] = '{' . $type . ',[' . implode(',', $item) . ']}';
            }
            // Format the items list in the general list, [{type1, [item1, item2], {type2,[item3,item4]}}]
            $orderedPeopleRequests[$user] = '[' . implode(',', $orderedPeopleRequests[$user]) . ']';
            // Inserts row to the database
            $summaryStmt->bind_param("isss", $peopleReqId[$user], $user, $peopleOrderedOn[$user], $orderedPeopleRequests[$user]);
            $summaryStmt->execute();
        }
        
        $result->free_result();

        return true;
    }

}

//////////////////////////////////////////////////
// Now configures endpoints below
//////////////////////////////////////////////////

// Saves the output before json encoding
$output = [];

// Creating connection, all queries requires it
$connection = new Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$connection->connect();

// For GET (read request, read item)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requests = new Request($connection->getConnection());

    // For get items filtered by type number (1, 2 or 3)
    if (!empty($_GET['action']) && $_GET['action'] == 'items') {
        $output = $requests->getItems($_GET['type']);

    // For get request by id, only one request
    } elseif (!empty($_GET['id'])) {
        $output = $requests->getRequestById($_GET['id']);
    } else {
        // For test only, fill summary table
        // $output = $requests->updateSummary();

        // For get all request table for table UI via JSON
        $output = $requests->getAllRequests();
    }

    // For GET, encoding output with json data
    echo json_encode($output);
    
// For POST (add request, edit request by id)
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requests = new Request($connection->getConnection());

    // If id is given, then edit request
    if (!empty($_GET['id'])) {
        $output = $requests->editRequest($_GET['id'], $_POST);

    // If not id given, add request
    } else {
        $output = $requests->addRequest($_POST);
    }
    // Encoding output (normally only 201/200), if the query has error the json is not valid for AJAX
    echo json_encode($output);

// For DELETE (delete request by id)
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $requests = new Request($connection->getConnection());
    $output = $requests->deleteRequest($_GET['id']);
    echo json_encode($output);

// Only for testing, no response because always is requested something
} else {
    echo json_encode($output);
}

// Closes database connection
$connection->closeConnection();