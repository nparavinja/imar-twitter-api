<?php


class Baza
{
    private $hostname="localhost";
    private $username="root";
    private $password="";
    private $dbname;
    private $dblink; // veza sa bazom
    private $result; // vraca rezultat upita
    private $records; // Holds the total number of records returned
    private $affected; // Holds the total number of affected rows

    function __construct($dbname)
    {
        $this->dbname = $dbname;
        $this->Connect();
    }
    
    function __destruct()
    {
    $this->dblink->close();
    //echo "Konekcija prekinuta";
    }
    
    public function getResult()
    {
        return $this->result;
    }
    //konekcija sa bazom
    function Connect()
    {
        $this->dblink = new mysqli($this->hostname, $this->username, $this->password, $this->dbname);
        if ($this->dblink ->connect_errno) {
        printf("Konekcija neuspešna");
        exit();
        }else {
           //echo "Uspesna konekcija na bazu!";
        }
        $this->dblink->set_charset("utf8");
        //echo "Uspesna konekcija";
    }

    function ExecuteQuery($query)
    {
        if($this->result = $this->dblink->query($query)){

       //     $this->records = $this->result->num_rows;
    //        $this->affected = $this->dblink->affected_rows;
            return true;
        }
        else
        {

            //echo "Neuspesan upit";
            return false;
        }
    }

        //select funkcija
        function select ($table, $rows = '*', $where = null, $order = null)
        {
            $q = 'SELECT '.$rows.' FROM '.$table;
            if($where != null)
                $q .= ' WHERE '.$where;
            if($order != null)
                $q .= ' ORDER BY '.$order;
            $this->ExecuteQuery($q);
            //print_r($this->getResult()->fetch_object());
        }

    function insert ($table,$rows,$values)
    {
        for($i = 0; $i < count($values); $i++) {
            
            $values[$i] = $this->dblink->real_escape_string($values[$i]);
               
             
        }

            $insert = 'INSERT INTO '.$table;
            if($rows != null)
            {
                $insert .= " (".$rows.")";
            }
			$insert .= ' VALUES(';
            $insert.="'";
			//$insert .="'".$values[0]."', '".$values[1]."', '".$values[2]."', '".$values[3]."', '".$values[4]."', '".$values[5]."', '".$values[6]."')";

            for($i = 0; $i < count($values); $i++) {
                if($i == count($values)-1){
                    $insert.=$values[$i]."')";
                    break;
                }
                 $insert.=$values[$i]."', '";
            }

           // echo "<br>";
//			echo $insert;

            $this->ExecuteQuery($insert);
      }

      function vratiSledeciKorisnikID()
      {
            $query = "SELECT * FROM Korisnik";
            $rezultat = $this->dblink->query($query);
            $brojac = 1;
            while($korisnik = $rezultat->fetch_object()){
                // print_r($korisnik);
                // echo "Korisnik: ".$korisnik->Username.", id: ".$korisnik->KorisnikID;
              //echo "<br>";
                  $brojac++;
                 }
            //echo "Brojac: ".$brojac;
            return $brojac;
        }


        function proveriKorisnika($username,$password)
        {
            $us = $this->dblink->real_escape_string($username);
            $pwd = $this->dblink->real_escape_string($password);
            $query = "SELECT * FROM Korisnik WHERE Username="."'".$us."'   AND Password = '".$pwd."' ";
            $rezultat = $this->dblink->query($query);

            //echo "<br>";
            $korisnik = $rezultat->fetch_object();
           if(isset($korisnik)){
               // echo $korisnik->Username.", postoji u bazi!";
               return $korisnik;
            }else {
              // echo "Ne postoji u bazi!";
               return 0;
           }
        }


        function vratiKorisnika($username)
        {
            $us = $this->dblink->real_escape_string($username);
            $query = "SELECT * FROM Korisnik WHERE UsernameTwitter = '".$us."'";
            $rezultat = $this->dblink->query($query);
            $korisnik = $rezultat->fetch_object();
            return $korisnik;
        }

        function vratiKorisnikaID($id)
        {
            $query = "SELECT * FROM Korisnik WHERE KorisnikID = ".$id;
            $rezultat = $this->dblink->query($query);
            $korisnik = $rezultat->fetch_object();
            return $korisnik;
        }


        function selectMax($table, $rows = '*', $where = null, $order = null)
        {
         $q = 'SELECT MAX('.$rows.') AS RedniBroj FROM '.$table;
            if($where != null)
                $q .= ' WHERE '.$where;
            if($order != null)
                $q .= ' ORDER BY '.$order;

            $this->ExecuteQuery($q);
          }


    function update ($table, $red, $vrednost, $tableid, $id)
    {

      $update = 'UPDATE '.$table." SET ".$red."='".$vrednost."' WHERE ".$tableid."=".$id;
			//echo $update;

        if (($this->ExecuteQuery($update)) && ($this->affected >0))
          return true;
            else return false;
    }
   



}
?>
