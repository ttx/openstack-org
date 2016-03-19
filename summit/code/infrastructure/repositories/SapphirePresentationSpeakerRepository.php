<?php

/**
 * Copyright 2015 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
final class SapphirePresentationSpeakerRepository extends SapphireRepository implements ISpeakerRepository
{

    private $cache = array();

    public function __construct()
    {
        parent::__construct(new PresentationSpeaker);
    }

    /**
     * @param string $term
     * @param int $limit
     * @return array;
     */
    public function searchByTerm($term, $limit = 10)
    {

        $term_split = explode(' ',$term);
        $term1 = '';
        $term2 = '';

        if(count($term_split) == 2)
        {
            $term1 = $term_split[0];
            $term2 = $term_split[1];
        }

        $member_sql = <<<SQL
SELECT
CONCAT(M.ID,'_',IFNULL(PS.ID , 0)) AS unique_id,
M.ID AS member_id ,
M.ID AS id, CONCAT(M.FirstName,' ',M.Surname,' (',IFNULL(M.Email , PSR.Email),')') AS name,
IFNULL(PS.ID , 0) AS speaker_id,
IFNULL(M.Email , PSR.Email) AS email
FROM Member AS M
LEFT JOIN PresentationSpeaker AS PS ON PS.MemberID = M.ID
LEFT JOIN SpeakerRegistrationRequest AS PSR ON PSR.SpeakerID = PS.ID
SQL;

        $speakers_sql = <<<SQL
SELECT
CONCAT(PS.MemberID,'_',IFNULL(PS.ID , 0)) AS unique_id,
PS.MemberID AS member_id ,
PS.ID AS id, CONCAT(PS.FirstName ,' ',PS.LastName,' (', PSR.Email, ')') AS name,
PS.ID  AS speaker_id,
PSR.Email AS email
FROM PresentationSpeaker AS PS
INNER JOIN SpeakerRegistrationRequest AS PSR ON PSR.SpeakerID = PS.ID
SQL;

        $member_conditions = array(
            "single"   => array(
                "M.FirstName LIKE '{$term}%'",
                "M.Surname   LIKE '{$term}%'",
                "M.Email LIKE '{$term}%'",
                "M.ID LIKE '{$term}%'",
            ),
            "combined" => "M.FirstName LIKE '{$term1}%' AND M.Surname LIKE '{$term2}%' ",
        );

        $speakers_conditions = array(
            "single"   => array(
                "PS.FirstName LIKE '{$term}%'",
                "PS.LastName   LIKE '{$term}%'",
                "PSR.Email LIKE '{$term}%'",
            ),
            "combined" => "PS.FirstName LIKE '{$term1}%' AND PS.Surname LIKE '{$term2}%' ",
        );

        $query = '';

        foreach($member_conditions as $type => $condition){
            if($type == 'single') {
                foreach($condition as $c) {
                    $query .= $member_sql . ' WHERE ' . $c;
                    $query .= ' UNION ';
                }
            }
            else if(!empty($term1) && !empty($term2))
            {
                $query .= $member_sql . ' WHERE ' . $condition;
                $query .= ' UNION ';
            }
        }

        foreach($speakers_conditions as $type => $condition){
            if($type == 'single') {
                foreach($condition as $c) {
                    $query .= $speakers_sql . ' WHERE ' . $c;
                    $query .= ' UNION ';
                }
            }
            else if(!empty($term1) && !empty($term2))
            {
                $query .= $member_sql . ' WHERE ' . $condition;
            }
        }
        $query = substr($query,0, strlen($query) - strlen(' UNION '));
        $query .= " ORDER BY `name` LIMIT 0, {$limit};";
        $res = DB::query($query);
        $data = array();
        foreach ($res as $row) {

            $data[] = array
            (
                'unique_id'  => $row['unique_id'],
                'member_id'  => $row['member_id'],
                'name'       => $row['name'],
                'speaker_id' => $row['speaker_id'],
                'email'      => $row['email'],
            );
        }
        return $data;
    }
    /**
     * @param ISummit $summit
     * @param string $term
     * @return IPresentationSpeaker[]
     */
    public function searchBySummitAndTerm(ISummit $summit, $term)
    {

        $speakers        = array();
        $summit_id       = $summit->getIdentifier();

        $sql_speakers = <<<SQL
      SELECT DISTINCT S.*, CONCAT(S.FirstName,' ',S.LastName) AS FullName FROM PresentationSpeaker S
      WHERE EXISTS
      (
            SELECT P.ID From Presentation P
            INNER JOIN SummitEvent E ON E.ID = P.ID AND E.Published = 1 AND E.SummitID = {$summit_id}
            INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
            WHERE PS.PresentationSpeakerID = S.ID
      )
      HAVING FullName LIKE '%{$term}%'
      UNION
      SELECT DISTINCT S.*, CONCAT(S.FirstName,' ',S.LastName) AS FullName FROM PresentationSpeaker S
      WHERE EXISTS
      (
            SELECT P.ID From Presentation P
            INNER JOIN SummitEvent E ON E.ID = P.ID AND E.Published = 1 AND E.SummitID = {$summit_id}
            INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
             WHERE PS.PresentationSpeakerID = S.ID
      )
      HAVING SOUNDEX(FullName) = SOUNDEX('{$term}')
      UNION
      SELECT DISTINCT S.*, CONCAT(S.FirstName,' ',S.LastName) AS FullName FROM PresentationSpeaker S
      WHERE EXISTS
      (
            SELECT P.ID From Presentation P
            INNER JOIN SummitEvent E ON E.ID = P.ID AND E.Published = 1 AND E.SummitID = {$summit_id}
            INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
            WHERE PS.PresentationSpeakerID = S.ID AND E.Title LIKE '%{$term}%'
      )
SQL;

        foreach(DB::query($sql_speakers) as $row)
        {
            $class = $row['ClassName'];
            array_push($speakers, new $class($row));
        }

        return $speakers;
    }

    /**
     * @param ISummit $summit
     * @param int $page
     * @param int $page_size
     * @param string $term
     * @param string $sort_by
     * @param string $sort_dir
     * @return array
     */
    public function getBySummit(ISummit $summit, $page= 1, $page_size = 10, $term = '', $sort_by = 'id', $sort_dir = 'asc')
    {
        $cache_key = $term;

        $offset = ($page - 1 ) * $page_size;
        $sort  = '';
        $where = '';
        if(!empty($term))
        {
            $where = " HAVING FullName LIKE  '%{$term}%' ";
        }
        switch(strtolower($sort_by))
        {
            case 'id':
                $sort = ' ORDER BY PresentationSpeaker.ID '.strtoupper($sort_dir);
                break;
            case 'fullname':
                $sort = ' ORDER BY FullName '.strtoupper($sort_dir);
                break;
            case 'email':
                $sort = ' ORDER BY Email '.strtoupper($sort_dir);
                break;
        }

        $query_count = <<<SQL
        SELECT COUNT(FullName) AS QTY FROM
        (
            SELECT
            IFNULL(CONCAT(PresentationSpeaker.FirstName,' ', PresentationSpeaker.LastName), CONCAT(Member.FirstName,' ', Member.Surname)) AS FullName
            FROM PresentationSpeaker
            LEFT JOIN Member ON Member.ID = PresentationSpeaker.MemberID
            LEFT JOIN SpeakerRegistrationRequest ON SpeakerRegistrationRequest.SpeakerID = PresentationSpeaker.ID
            {$where}
        ) AS P;
SQL;
        $query = <<<SQL
SELECT DISTINCT PresentationSpeaker.*,
IFNULL(CONCAT(PresentationSpeaker.FirstName,' ', PresentationSpeaker.LastName), CONCAT(Member.FirstName,' ', Member.Surname)) AS FullName,
IFNULL(Member.Email, SpeakerRegistrationRequest.Email) AS Email
FROM PresentationSpeaker
LEFT JOIN Member ON Member.ID = PresentationSpeaker.MemberID
LEFT JOIN SpeakerRegistrationRequest ON SpeakerRegistrationRequest.SpeakerID = PresentationSpeaker.ID
{$where}
{$sort} LIMIT {$offset}, {$page_size};
SQL;


        $count_res = DB::query($query_count)->first();
        $res       = DB::query($query);
        $count     = intval($count_res['QTY']);
        $data      = array();
        foreach($res as $row)
        {
            array_push($data, new PresentationSpeaker($row));
        }

        //add cache results
        foreach($this->cache as $cache_name => $cache_speaker) {
            if (strpos($cache_name,strtolower($term)) !== false) {
                array_push($data, $cache_speaker);
            }
        }

        return array($page, $page_size, $count, $data);
    }

    /**
     * @param int $member_id
     * @return IPresentationSpeaker
     */
    public function getByMemberID($member_id)
    {
        return PresentationSpeaker::get()->filter('MemberID', $member_id)->first();
    }

    /**
     * @param IEntity $entity
     * @return int|void
     */
    public function add(IEntity $entity)
    {
        parent::add($entity);
        $this->cache[strtolower($entity->getName())] = $entity;
    }
}
