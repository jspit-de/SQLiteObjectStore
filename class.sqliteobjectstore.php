<?php
/**
.---------------------------------------------------------------------------.
|  Software: SQLiteObjectStore - PHP class                                  |
|   Version: 1.1                                                            |
|      Date: 22.12.2017                                                     |
|      Site:                                                                |
| ------------------------------------------------------------------------- |
| Copyright (c) 2017-2018, Peter Junk alias jspit All Rights Reserved.      |
' ------------------------------------------------------------------------- '
|   License: Distributed under the Lesser General Public License (LGPL)     |
|            http://www.gnu.org/copyleft/lesser.html                        |
| This program is distributed in the hope that it will be useful - WITHOUT  |
| ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or     |
| FITNESS FOR A PARTICULAR PURPOSE.                                         |
'---------------------------------------------------------------------------'
*/

class SQLiteObjectStore
{
    private $pdo;

    /*
     * Constructs the class instance
     * @param filename filename for SQLite
     * @param deleteOld false then old records will not removed 
     */
    public function __construct($filename = ':memory:', $deleteOld = true)
    {
      $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        
      );
      $this->pdo = new PDO('sqlite:'.$filename,null,null,$options);

      $sql = 'CREATE TABLE IF NOT EXISTS store (
             datakey TEXT UNIQUE, 
             data TEXT , 
             expires DATETIME) ';

      $this->pdo->query($sql);

      if($deleteOld) $this->deleteOld();
    }
    
   /*
    * Destructor
    * close DB
    */
    public function __destruct()
    {
      $this->pdo = null;
    }
    
    /**
     * set
     *
     * @param key (string)
     * @param data a value(int,string,..),array or object
     *  note: resources,closures and objects with closures cant save 
     * @param expiresIn string with of the expiration point in time
     *   expiresIn fix Date how '2017-12-01' or relative Date '2 days'
     * @return true if ok
     */
    public function set($key, $data, $expires = '90 Seconds')
    {
      $sql = 'INSERT OR REPLACE INTO store
        (datakey, data, expires)
      VALUES 
        (:datakey, :data, :expires)';

        $stmt = $this->pdo->prepare($sql);
        
        $para = array(
          'datakey' => $key,
          'data' => serialize($data),
          'expires' => $this->toDateTime($expires)->format('Y-m-d H:i:s')
        );

        return $stmt->execute($para);
    }

    /*
     * get
     *
     * @return the object from store
     * @param key string
     * @return false if key not exists
     *  note: if object may be boolean use keyExists before
     */
    public function get($key)
    {
      if (!$this->exists($key)) {
        return false;
      }

      $sql = 'SELECT data FROM store WHERE datakey = :datakey';
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(array('datakey' => $key));
      $data = $stmt->fetchColumn();

      return unserialize($data);
    }

    /*
     * keyExists
     *
     * @param key
     * @return true or false
    */
    public function exists($key)
    {
      $sql = 'SELECT 1 FROM store WHERE datakey = :datakey LIMIT 1';
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(array('datakey' => $key));
      return (bool) $stmt->fetchColumn();
    }
    
    /*
     * set expires
     *
     * @param key
     * @param expires DateTime as string, timestamp or DateTime-Object
     * @return true if set ok
     * @return false if key not exists
     */
    public function setExpires($key, $expires)
    {
      if ( ! $this->exists($key) ) {
        return false;
      }

      $sql = 'UPDATE store SET expires = :expires WHERE datakey = :datakey';
      $stmt = $this->pdo->prepare($sql);
      $param = array(
        'datakey' => $key,
        'expires' => $this->toDateTime($expires)->format('Y-m-d H:i:s')
      );
      $stmt->execute($param);
      
      return (bool)$stmt->rowCount();
    }

    /*
     * get expires as DateTime
     *   note: without microseconds
     * @param key
     * @return true if set ok
     * @return false if key not exists
     */
    public function getExpires($key)
    {
      if ( ! $this->exists($key) ) {
        return false;
      }

      $sql = 'SELECT expires FROM store WHERE datakey = :datakey';
      $stmt = $this->pdo->prepare($sql);
      $param = array('datakey' => $key);
      $stmt->execute($param);
      $strDate = $stmt->fetchColumn();
      
      return $strDate ? date_create($strDate) : false;
    }
    

   /*
    * remove
    *
    * Deletes entry with key from the store.
    * @param key.
    * @return true if record remove, false if not
    */ 
    public function delete($key)
    {
      $sql = 'DELETE FROM store WHERE datakey = :datakey';
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(array('datakey' => $key));

      return (bool)$stmt->rowCount();
    }

    /**
     * deleteOld
     *
     * Deletes every outdated entry from the store.
     * @return count of removed records
     */
    public function deleteOld()
    { 
      $sql = 'DELETE FROM store WHERE expires < :expires';
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(array('expires' => date('Y-m-d H:i:s')));
      return $stmt->rowCount();
    }
 
    //internal only for expire time
    private function toDateTime($par)
    {
      if(is_string($par)) return date_create($par);
      if(is_int($par)) return date_create(date('Y-m-d H:i:s',$par));
      if($par instanceof DateTime) return $par;
      return false;
    }

}