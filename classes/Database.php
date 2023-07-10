<?php

class Database
{

    private $name = 'root';
    private $password = '';

    private $connection;

    public function __construct()
    {
        $this->connect();
    }

    public function connect()
    {
        try {
            $this->connection = new PDO('mysql:host=localhost;dbname=bloodbank', $this->name, $this->password);
            // set the PDO error mode to exception
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function FetchAll($table)
    {
        $query = "SELECT * FROM " . $table;
        $statement = $this->connection->prepare($query);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    public function Authenticate($hospitalname, $hospitalid)
    {

        $query = "SELECT hospital_id, name FROM hospital WHERE hospital_id = :hospitalid AND name = :hospitalname";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(':hospitalid', $hospitalid, PDO::PARAM_INT);
        $statement->bindParam(':hospitalname', $hospitalname);
        $statement->execute();
        $res = $statement->fetch(PDO::FETCH_ASSOC);
        return (!$res) ? false : true;

    }
    public function AuthenticateName($hospitalname)
    {

        $query = "SELECT name FROM hospital WHERE name = :hospitalname";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(':hospitalname', $hospitalname);
        $statement->execute();
        $res = $statement->fetch(PDO::FETCH_ASSOC);
        return (!$res) ? false : true;

    }

    public function getDonors()
    {
        $query = "SELECT Donor.*, Blood.*, Donates.donation_date, Donates.donated_bag, Donates.donation_id
        FROM Donates
        JOIN Donor ON Donates.donor_id = Donor.donor_id
        JOIN Blood ON Donates.blood_id = Blood.blood_id;";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }

    public function getRecentDonors()
    {
        $query = "SELECT Donor.*, Blood.*, Donates.donation_date, Donates.donated_bag
        FROM Donates
        JOIN Donor ON Donates.donor_id = Donor.donor_id
        JOIN Blood ON Donates.blood_id = Blood.blood_id 
        ORDER BY Donates.donation_date DESC;";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }

    public function getSpecificTypes($bloodType, $rhType)
    {
        $query = "SELECT Donor.*, Blood.*, Donates.donation_date, Donates.donated_bag, Donates.donation_id
        FROM Donates
        JOIN Donor ON Donates.donor_id = Donor.donor_id
        JOIN Blood ON Donates.blood_id = Blood.blood_id
        WHERE Blood.type = ? AND Blood.rh = ?
        ORDER BY Donates.donation_date DESC;";
        $statement = $this->connection->prepare($query);
        $statement->execute([$bloodType, $rhType]);
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }
    public function getRecentRequestSpecificTypes($bloodType, $rhType)
    {
        $query = "SELECT R.*, B.type AS type, B.rh AS rh, H.name AS name, H.address AS address
    FROM Request R
    JOIN Blood B ON R.blood_id = B.blood_id
    JOIN Hospital H ON R.hospital_id = H.hospital_id
    WHERE B.type = :bloodType
    AND B.rh = :rhType
    ORDER BY R.requested_at DESC";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(':bloodType', $bloodType);
        $statement->bindParam(':rhType', $rhType);
        $statement->execute();
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }
    public function getAllUnapprovedRequests()
    {
        $query = "SELECT Request.*, Hospital.name AS hospital_name, Blood.type, Blood.rh
        FROM Request
        JOIN Hospital ON Request.hospital_id = Hospital.hospital_id
        JOIN Blood ON Request.blood_id = Blood.blood_id
        WHERE Request.is_approved = FALSE;";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }

    public function getAllApprovedRequests()
    {
        $query = "SELECT Request.*, Hospital.name AS hospital_name, Blood.type, Blood.rh
        FROM Request
        JOIN Hospital ON Request.hospital_id = Hospital.hospital_id
        JOIN Blood ON Request.blood_id = Blood.blood_id
        WHERE Request.is_approved = TRUE;";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }
    public function getAllRequests()
    {
        $query = "SELECT Request.*, Blood.*, Hospital.*
        FROM Request
        JOIN Blood ON Request.blood_id = Blood.blood_id
        JOIN Hospital ON Request.hospital_id = Hospital.hospital_id;
        ";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }

    public function getAllCurrentRequests()
    {
        $query = "SELECT Request.*, Blood.*, Hospital.*
        FROM Request
        JOIN Blood ON Request.blood_id = Blood.blood_id
        JOIN Hospital ON Request.hospital_id = Hospital.hospital_id
        WHERE Request.progress = FALSE;
        ";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }
    public function getAllAvailableBloods()
    {
        $query = "
            SELECT * from Blood WHERE bags < 0;
        ";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }
    public function getAllStocks()
    {
        $query = "
            SELECT * from Blood WHERE bags > 0;
        ";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }

    public function getBags($bloodId)
    {
        $query = "SELECT bags FROM blood WHERE blood_id = :bloodId";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(':bloodId', $bloodId);
        $statement->execute();
        $bags = $statement->Fetch(PDO::FETCH_COLUMN);
        return $bags;
    }

    public function bagsUpdate($bloodId, $quantity)
    {
        try {
            $availableBags = $this->getBags($bloodId);
            $quantity = intval($quantity);
            if ($availableBags < $quantity) {
                return false;
            }
            $newNumberOfBags = $availableBags - $quantity;
            if ($newNumberOfBags > 0) {
                $query = "UPDATE blood SET bags = ? WHERE blood_id = ?";
                $statement = $this->connection->prepare($query);
                $statement->execute([$newNumberOfBags, $bloodId]);
            } else {
                return false;
            }
            return true;
        } catch (PDOException $e) {
            // Handle any potential errors
            echo "Error updating bags: " . $e->getMessage();
            return false; // Update failed (error occurred)
        }
    }
    public function getRecentDonorsSpecificTypes($bloodType, $rhType)
    {
        $query = "SELECT Donor.*, Blood.*, Donates.donation_date,Donates.donation_id, Donates.donated_bag
        FROM Donates
        JOIN Donor ON Donates.donor_id = Donor.donor_id
        JOIN Blood ON Donates.blood_id = Blood.blood_id
        WHERE Blood.type = ? AND Blood.rh = ?
        ORDER BY Donates.donation_date DESC;";
        $statement = $this->connection->prepare($query);
        $statement->execute([$bloodType, $rhType]);
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }

    public function appendBloodStock($bloodId, $quantity)
    {
        try {
            $availableBags = $this->getBags($bloodId);
            $newNumberOfBags = intval($availableBags) + $quantity;
            $query = "UPDATE blood SET bags = ? WHERE blood_id = ?";
            $statement = $this->connection->prepare($query);
            $statement->execute([$newNumberOfBags, $bloodId]);

            // Check if the update query executed successfully
            if ($statement->rowCount() > 0) {
                return true; // Update successful
            } else {
                return false; // Update failed (no rows affected)
            }
        } catch (PDOException $e) {
            // Handle any potential errors
            echo "Error updating bags: " . $e->getMessage();
            return false; // Update failed (error occurred)
        }
    }
    public function RequestBloods($bloodid, $hospitalId, $doctor, $type, $rh, $bloodQuantity)
    {
        $available = true;
        $availableBloodId = $this->getBloodTableId($type, $rh);

        $currentDate = date('Y-m-d H:i:s');
        $isApproved = false;
        $progress = 'Pending';

        if ($available) {
            $query = "
                INSERT INTO Request (blood_id, hospital_id, doctor, progress, is_approved, requested_at, requested_bag)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";

            $statement = $this->connection->prepare($query);
            $statement->bindParam(1, $bloodid);
            $statement->bindParam(2, $hospitalId);
            $statement->bindParam(3, $doctor);
            $statement->bindParam(4, $progress);
            $statement->bindParam(5, $isApproved);
            $statement->bindParam(6, $currentDate);
            $statement->bindParam(7, $bloodQuantity);

            try {
                $statement->execute();
                return true;
            } catch (PDOException $e) {
                // Handle the exception, e.g., log the error or display an error message
                // based on your application's requirements
                echo "Error adding blood request: " . $e->getMessage();
            }
        }

        return false; // Return false if an error occurred or blood is not available
    }

    public function RequestBlood($hospitalId, $doctor, $type, $rh, $bloodQuantity)
    {
        $available = true;
        $availableBloodId = $this->getBloodTableId($type, $rh);
        $currentDate = date('Y-m-d H:i:s');
        $isApproved = false;
        $progress = 'Pending';

        if ($available) {
            $query = "
                INSERT INTO Request (blood_id, hospital_id, doctor, progress, requested_at, requested_bag)
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $statement = $this->connection->prepare($query);
            $statement->bindParam(1, $availableBloodId);
            $statement->bindParam(2, $hospitalId);
            $statement->bindParam(3, $doctor);
            $statement->bindParam(4, $progress);
            $statement->bindParam(5, $currentDate);
            $statement->bindParam(6, $bloodQuantity);

            try {
                $statement->execute();
                return true;
            } catch (PDOException $e) {
                // Handle the exception, e.g., log the error or display an error message
                // based on your application's requirements
                echo "Error adding blood request: " . $e->getMessage();
            }
        }

        return false; // Return false if an error occurred or blood is not available
    }


    public function updateStatusToInProgress($requestId)
    {
        // Assuming you have a database connection established

        // Prepare the update query
        $query = "UPDATE Request SET progress = 'In Progress' WHERE request_id = ?";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(1, $requestId);

        try {
            // Execute the update query
            $statement->execute();
        } catch (PDOException $e) {
            // Handle the exception, e.g., log the error or display an error message
            echo "Error updating status: " . $e->getMessage();
        }
    }
    public function updateStatusToComplete($requestId)
    {
        // Assuming you have a database connection established

        // Prepare the update query
        $query = "UPDATE Request SET progress = 'Complete' WHERE request_id = ?";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(1, $requestId);

        try {
            // Execute the update query
            $statement->execute();
        } catch (PDOException $e) {
            // Handle the exception, e.g., log the error or display an error message
            echo "Error updating status: " . $e->getMessage();
        }
    }
    private function reqbagcount($requestId)
    {
        $query = "SELECT requested_bag FROM Request WHERE request_id = ?";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(1, $requestId);
        $statement->execute();
        $bags = $statement->fetchColumn();
        return $bags;
    }

    private function minusBloodStock($requestId)
    {
        // Get the requested bags count
        $requestedBags = $this->reqbagcount($requestId);

        // Update the blood stock by subtracting the requested bags
        $query = "UPDATE Blood 
              SET bags = bags - :requestedBags 
              WHERE blood_id = (
                SELECT blood_id 
                FROM Request 
                WHERE request_id = :requestId
              )";

        $statement = $this->connection->prepare($query);
        $statement->bindParam(':requestedBags', $requestedBags);
        $statement->bindParam(':requestId', $requestId);

        try {
            // Execute the update query
            $statement->execute();
        } catch (PDOException $e) {
            // Handle the exception, e.g., log the error or display an error message
            echo "Error updating blood stock: " . $e->getMessage();
        }
    }


    public function cancelRequest($requestId)
    {
        // Assuming you have a database connection established

        // Prepare the update query
        $query = "UPDATE Request SET progress = 'Cancel' WHERE request_id = ?";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(1, $requestId);

        try {
            // Execute the update query
            $statement->execute();
        } catch (PDOException $e) {
            // Handle the exception, e.g., log the error or display an error message
            echo "Error canceling request: " . $e->getMessage();
        }
    }

    public function getRequestsByHospital($hospitalName)
    {
        $query = "SELECT Request.*, Hospital.*
              FROM Request
              JOIN Hospital ON Request.hospital_id = Hospital.hospital_id
              WHERE Hospital.name = ?";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(1, $hospitalName);
        $statement->execute();
        $res = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }

    public function addDonationRequest($fname, $mname, $lname, $sex, $type, $rh, $contact, $age, $address, $donatedBag, $medicalStatus)
    {
        // Insert data into `donor` table
        $donorQuery = "INSERT INTO donor (fname, mname, lname, sex, contact, age, address,medical_status)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $donorStatement = $this->connection->prepare($donorQuery);
        $donorResult = $donorStatement->execute([$fname, $mname, $lname, $sex, $contact, $age, $address, $medicalStatus]);
        $donorId = $this->connection->lastInsertId();

        $bloodId = $this->getBloodTableId($type, $rh);
        // Insert data into `donates` table
        $updatebag = $this->appendBloodStock($bloodId, $donatedBag);
        $currentDate = date('Y-m-d H:i:s');
        $donatesQuery = "INSERT INTO donates (donor_id, blood_id, donation_date, donated_bag)
                     VALUES (?, ?, ?,?)";
        $donatesStatement = $this->connection->prepare($donatesQuery);
        $donatesResult = $donatesStatement->execute([$donorId, $bloodId, $currentDate, $donatedBag]);

        // Check if all queries were successful
        if ($updatebag && $donatesResult && $donorResult) {
            return true;
        } else {
            return false;
        }
    }
    public function getSpecificBlood($type, $rh)
    {
        // Assuming you have a database connection established

        // Prepare the SQL statement
        $sql = "SELECT * FROM blood WHERE type = :bloodtype AND rh = :rh";

        // Prepare the statement
        $stmt = $this->connection->prepare($sql);

        // Bind the parameters
        $stmt->bindParam(':bloodtype', $type);
        $stmt->bindParam(':rh', $rh);

        // Execute the query
        $stmt->execute();

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if a row was returned
        if ($result) {
            // Return the ID of the blood table
            return $result;
        }

        // If no row was found, return null or any other suitable value
        return null;
    }


    public function getBloodTableId($type, $rh)
    {
        // Assuming you have a database connection established

        // Prepare the SQL statement
        $sql = "SELECT blood_id FROM blood WHERE type = :bloodtype AND rh = :rh";

        // Prepare the statement
        $stmt = $this->connection->prepare($sql);

        // Bind the parameters
        $stmt->bindParam(':bloodtype', $type);
        $stmt->bindParam(':rh', $rh);

        // Execute the query
        $stmt->execute();

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if a row was returned
        if ($result) {
            // Return the ID of the blood table
            return $result['blood_id'];
        }

        // If no row was found, return null or any other suitable value
        return null;
    }



    public function updateDonationRequest($donorId, $fname, $mname, $lname, $sex, $type, $rh, $contact, $age, $address, $donatedBag, $medicalStatus)
    {
        // Update data in `donor` table
        $donorQuery = "UPDATE donor SET fname = ?, mname = ?, lname = ?, sex = ?, contact = ?, age = ?, address = ?, medical_status = ? WHERE donor_id = ?";
        $donorStatement = $this->connection->prepare($donorQuery);
        $donorResult = $donorStatement->execute([$fname, $mname, $lname, $sex, $contact, $age, $address, $medicalStatus, $donorId]);

        // Check if the donor update was successful
        if (!$donorResult) {
            return false;
        }

        $bloodid = $this->getBloodTableId($type, $rh);

        // Update data in `blood` table
        $bloodQuery = "UPDATE donates SET blood_id = ? WHERE donor_id = ?";
        $bloodStatement = $this->connection->prepare($bloodQuery);
        $bloodResult = $bloodStatement->execute([$bloodid, $donorId]);

        // Check if the blood update was successful
        if (!$bloodResult) {
            return false;
        }

        // Update data in `donates` table
        $donatesQuery = "UPDATE donates SET donated_bag = ? WHERE donor_id = ?";
        $donatesStatement = $this->connection->prepare($donatesQuery);
        $donatesResult = $donatesStatement->execute([$donatedBag, $donorId]);

        // Check if the donates update was successful
        if (!$donatesResult) {
            return false;
        }

        return true;
    }

    function getNamesStartingWith($prefix)
    {

        $query = "SELECT Donor.*, Blood.*, Donates.donation_date, Donates.donated_bag, Donates.donation_id
        FROM Donates
        JOIN Donor ON Donates.donor_id = Donor.donor_id
        JOIN Blood ON Donates.blood_id = Blood.blood_id
        WHERE Donor.fname LIKE :prefix";
        // Prepare the statement
        $stmt = $this->connection->prepare($query);

        // Bind the parameter
        $stmt->bindValue(':prefix', $prefix . '%');

        // Execute the statement
        $stmt->execute();
        $matchingNames = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $matchingNames;
    }

    public function getRequestsByDoctorPrefix($prefix)
    {
        try {
            $query = "SELECT Hospital.*, Blood.*, Request.request_id, Request.blood_id, Request.hospital_id, Request.doctor, Request.progress, Request.requested_at, Request.requested_bag
          FROM Request
          JOIN Hospital ON Request.hospital_id = Hospital.hospital_id
          JOIN Blood ON Request.blood_id = Blood.blood_id
          WHERE Request.doctor LIKE :prefix";

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':prefix', $prefix . '%', PDO::PARAM_STR);
            $statement->execute();

            $requests = $statement->fetchAll(PDO::FETCH_ASSOC);

            return $requests;

        } catch (PDOException $e) {
            // Handle any potential errors
            echo "Error retrieving requests: " . $e->getMessage();
            return false; // Return false or handle the error as per your requirement
        }
    }

    public function getHospitalId($name)
    {
        try {
            $query = "SELECT hospital_id FROM hospital WHERE name = ?";
            $statement = $this->connection->prepare($query);
            $statement->execute([$name]);

            $result = $statement->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                return $result['hospital_id'];
            } else {
                return null;
            }
        } catch (PDOException $e) {
            // Handle any potential errors
            echo "Error retrieving hospital ID: " . $e->getMessage();
            return null; // Return null or handle the error as per your requirement
        }
    }

    public function getRequestsByHospitalPrefix($prefix)
    {
        $hospitalid = $this->getHospitalId($prefix);
        try {
            $query = "SELECT Hospital.*, Blood.*, Request.request_id, Request.blood_id, Request.hospital_id, Request.doctor, Request.progress, Request.requested_at, Request.requested_bag
            FROM Request
            JOIN Hospital ON Request.hospital_id = Hospital.hospital_id
            JOIN Blood ON Request.blood_id = Blood.blood_id
            WHERE Request.hospital_id = :hospital_id";

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':hospital_id', $hospitalid);
            $statement->execute();

            $requests = $statement->fetchAll(PDO::FETCH_ASSOC);

            return $requests;
        } catch (PDOException $e) {
            // Handle any potential errors
            echo "Error retrieving requests: " . $e->getMessage();
            return false; // Return false or handle the error as per your requirement
        }
    }


}