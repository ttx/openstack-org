<?php
/**
 * Copyright 2014 Openstack Foundation
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
/**
 * Class ElectionManager
 */
final class ElectionManager {

	/**
	 * @var IEntityRepository
	 */
	private $election_repository;

	/**
	 * @var
	 */
	private $foundation_member_repository;

	/**
	 * @var IEntityRepository
	 */
	private $vote_repository;

	/**
	 * @var IVoterFileRepository
	 */
	private $voter_file_repository;

	/**
	 * @var IVoteFactory
	 */
	private $vote_factory;

	/**
	 * @var IVoterFileFactory
	 */
	private $voter_file_factory;

	/**
	 * @var ITransactionManager
	 */
	private $tx_manager;

	/**
	 * @var IElectionFactory
	 */
	private $election_factory;

	/**
	 * @param IEntityRepository    $election_repository
	 * @param IEntityRepository    $foundation_member_repository
	 * @param IEntityRepository    $vote_repository
	 * @param IVoterFileRepository $voter_file_repository
	 * @param IVoteFactory         $vote_factory
	 * @param IVoterFileFactory    $voter_file_factory
	 * @param IElectionFactory     $election_factory
	 * @param ITransactionManager  $tx_manager
	 */
	public function __construct(IEntityRepository    $election_repository,
	                            IEntityRepository    $foundation_member_repository,
								IEntityRepository    $vote_repository,
								IVoterFileRepository $voter_file_repository,
	                            IVoteFactory         $vote_factory,
	                            IVoterFileFactory    $voter_file_factory,
								IElectionFactory     $election_factory,
								ITransactionManager   $tx_manager){

		$this->election_repository          = $election_repository;
		$this->foundation_member_repository = $foundation_member_repository;
		$this->vote_repository              = $vote_repository;
		$this->voter_file_repository        = $voter_file_repository;
		$this->voter_file_factory           = $voter_file_factory;
		$this->vote_factory                 = $vote_factory;
		$this->election_factory             = $election_factory;
		$this->tx_manager                   = $tx_manager;
	}


	/**
	 * @param string   $filename
	 * @param int      $election_id
	 * @param DateTime $open_date
	 * @param DateTime $close_date
	 * @return array
	 */
	public function ingestVotersForElection($filename, $election_id, DateTime $open_date, DateTime $close_date){


		return $this->tx_manager->transaction(function() use ($filename, $election_id, $open_date, $close_date){

			if($this->voter_file_repository->getByFileName($filename))
				throw new EntityAlreadyExistsException('VoterFile',sprintf('filename = %s',$filename));


			$election =  $this->election_repository->getById($election_id);
			if(!$election){
				$election = $this->election_factory->build($election_id, $open_date, $close_date);
				$election->write();
			}
			$reader   = new CSVReader($filename);

			$line          = false;
			$header        = $reader->getLine();
			$count         = 0;
			$not_processed = [];
            $already_voted = [];
			while($line = $reader->getLine()){

                $first_name        = $line[1];
				$last_name         = $line[2];
				$member_id         = (int)$line[3];
				$members_2_process = [];
				$member            = $this->foundation_member_repository->getById($member_id);

                echo sprintf("processing member id %s - first_name %s - last_name %s", $member_id, $first_name, $last_name).PHP_EOL;
                if(is_null($member)){
                    echo sprintf("cant find member by id %s. trying by first_name (%s) - last_name (%s)", $member_id, $first_name, $last_name).PHP_EOL;
                    // possible returns a list (array)
                    $members_2_process = $this->foundation_member_repository->getByCompleteName($first_name, $last_name);
                }
                else $members_2_process[] = $member;

                if(count($members_2_process) == 0)
                {
                    echo sprintf("cant find matches for member id %s - first_name %s - last_name %s on db. skipping it", $member_id, $first_name, $last_name).PHP_EOL;
                    $not_processed[] = ['id' => $member_id, 'first_name' => $first_name, 'last_name' => $last_name ];
                    continue;
                }

                foreach($members_2_process as $member_2_process) {

                    if (!$member_2_process->isFoundationMember()) {
                        echo sprintf("member id %s - first_name %s - last_name %s is not foundation member. skipping it ...", $member_id, $first_name, $last_name).PHP_EOL;
                        $not_processed[] = ['id' => $member_id, 'first_name' => $first_name, 'last_name' => $last_name];
                        continue;
                    }

                    if(in_array($member_2_process->ID, $already_voted)){
                        echo sprintf("member id %s - first_name %s - last_name %s already voted as member id %s", $member_id, $first_name, $last_name, $member_2_process->ID).PHP_EOL;
                        $not_processed[] = ['id' => $member_id, 'first_name' => $first_name, 'last_name' => $last_name];
                        continue;
                    }

                    echo sprintf("processed member id %s - first_name %s - last_name %s OK", $member_id, $first_name, $last_name).PHP_EOL;
                    $vote = $this->vote_factory->buildVote($election, $member_2_process);
                    $vote->write();
                    $already_voted[] = $member_2_process->ID;
                    $count++;
                }
         	}

			$voter_file = $this->voter_file_factory->build($filename);
			$voter_file->write();

			return array($count, $not_processed);
		});
	}
} 