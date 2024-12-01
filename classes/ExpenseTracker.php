<?php
$filepath = realpath(dirname(__FILE__));

include_once($filepath . "/../libs/CrudOperation.php");

class ExpenseTracker {
    private $db;
    function __construct()
    {
        $this->db = new CrudOperation();
    }

    function dbcon()
    {
        return $this->db->link;
    }

    public function addExpense($category, $amount, $description, $date) {
        $sql = "INSERT INTO tbl_expenses (category, amount, description, date) 
                VALUES ('$category', '$amount', '$description', '$date')";
        

        return $this->db->insert($sql) ? true : false;
    }

    public function getExpenses($start_date = null, $end_date = null, $category = null) {
        $sql = "SELECT * FROM tbl_expenses WHERE 1=1";

        if ($start_date && $end_date) {
            $sql .= " AND date BETWEEN '$start_date' AND '$end_date'";
        }

        if ($category) {
            $sql .= " AND category = '$category'";
        }

        $sql .= " ORDER BY date DESC";

        $result = $this->db->select($sql);
        return $result;
    }

    public function getTotalExpenses($start_date = null, $end_date = null) {
        $sql = "SELECT SUM(amount) as total FROM tbl_expenses WHERE 1=1";

        if ($start_date && $end_date) {
            $sql .= " AND date BETWEEN '$start_date' AND '$end_date'";
        }

        $result = $this->db->select($sql);

        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    public function getExpenseById($id) {
        $sql = "SELECT * FROM tbl_expenses WHERE id ='$id'";
        $result = $this->db->select($sql);

        return $result->fetch_assoc();
    }

    public function updateExpense($id, $category, $amount, $description, $date) {
        $sql = "UPDATE tbl_expenses SET category = '$category', amount = '$amount', description = '$description', date = '$date' WHERE id ='$id'";
        
        return $this->db->update($sql) ? true : false;
    }

    public function deleteExpense($id) {
        $sql = "DELETE FROM tbl_expenses WHERE id = '$id'";
        return $this->db->delete($sql) ? true : false;
    }
}
?>