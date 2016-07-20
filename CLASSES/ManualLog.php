<?php
require_once('../../CLASSES/ClassParent.php');
class ManualLog extends ClassParent {

    var $pk = NULL;
    var $employees_pk = NULL;
    var $time_log = NULL;
    var $reason = NULL;
    var $date_created = NULL;
    var $archived = NULL;
    var $type = NULL;
    

    public function __construct(    
                                    $pk ,
                                    $employees_pk ,
                                    $time_log ,
                                    $reason,
                                    $date_created,
                                    $archived,
                                    $type
                                   
                                )
    {
        
        $fields = get_defined_vars();
        
        if(empty($fields)){
            return(FALSE);
        }
        //sanitize
        foreach($fields as $k=>$v){
            $this->$k = pg_escape_string(trim(strip_tags($v)));
        }

        return(true);
    }

    public function fetch(){

         $sql = <<<EOT
                select
                    pk, 
                    (select first_name||' '||last_name from employees where pk = employees_pk) as name,
                    time_log :: time as time,
                    date_created::date as datecreated,
                    type,
                    (select status from manual_log_statuses where pk = manual_log.pk) as status
                from manual_log
                where archived = false
                ;
EOT;

        return ClassParent::get($sql);

    }

    public function update($extra){
    foreach($extra as $k=>$v){
            $extra[$k] = pg_escape_string(trim(strip_tags($v)));
        }
    $pk = $extra['pk'];
    $status = $extra['status'];


         $sql = <<<EOT
                update manual_log_statuses set
                
                    status
                =
                    '$status'
                
                where pk = $pk;
                ;
EOT;

        return ClassParent::insert($sql);

    }

    public function save_manual_log($extra){
        foreach($extra as $k=>$v){
            $extra[$k] = pg_escape_string(trim(strip_tags($v)));
        }
        $employees_pk = $this->employees_pk;
        $time_log = $this->time_log;
        $reason= $this->reason;
        $type= $this->type;

        $sql = 'begin;';
        $sql .= <<<EOT
                insert into manual_log
                (
                    employees_pk,
                    time_log,
                    reason,
                    type
                )
                values
                (    
                    $employees_pk,
                    '$time_log',
                    '$reason',
                    '$type'
                )
                returning pk
                ;
EOT;
        
        $supervisor_pk = $extra['supervisor_pk'];
        $sql .= <<<EOT
                insert into notifications
                (   
                    notification,
                    table_from,
                    table_from_pk,
                    employees_pk
                
                )
                values
                (    
                    'New manual log filed.',
                    'manual_log',
                    currval('manual_log_pk_seq'),
                    $supervisor_pk
                )
                ;
EOT;
        $sql .= <<<EOT
                insert into manual_log_statuses
                (
                    pk,
                    status          
                )
                values
                (    
                    currval('manual_log_pk_seq'),
                    'Pending'
                )
                ;
EOT;
        $sql .= "commit;";



        return ClassParent::insert($sql);   

    }


}

?>