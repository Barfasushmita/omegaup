<?php

/**
 * Description of ContestScoreboardTest
 *
 * @author joemmanuel
 */


class ContestScoreboardTest extends OmegaupTestCase {
	
	/**
	 * Basic test of scoreboard, shows at least the run 
	 * just submitted
	 */
	public function testBasicScoreboard() {
		
		// Get a problem
		$problemData = ProblemsFactory::createProblem();

		// Get a contest 
		$contestData = ContestsFactory::createContest();

		// Add the problem to the contest
		ContestsFactory::addProblemToContest($problemData, $contestData);

		// Create our contestants
		$contestant = UserFactory::createUser();
		$contestant2 = UserFactory::createUser();
		$contestant3 = UserFactory::createUser();
		
		// Create runs
		$runData = RunsFactory::createRun($problemData, $contestData, $contestant);
		$runData2 = RunsFactory::createRun($problemData, $contestData, $contestant2);
		$runData3 = RunsFactory::createRun($problemData, $contestData, $contestant3);
		
		// Grade the runs
		RunsFactory::gradeRun($runData);
		RunsFactory::gradeRun($runData2, 90);
		RunsFactory::gradeRun($runData3, 100);
		
		// Create request
		$r = new Request();
		$r["contest_alias"] = $contestData["request"]["alias"];
		$r["auth_token"] = $this->login($contestant);
		
		// Create API
		$response = ContestController::apiScoreboard($r);								
		
		// Validate that we have ranking
		$this->assertEquals(3, count($response["ranking"]));
		
		$this->assertEquals($contestant->getUsername(), $response["ranking"][0]["username"]);
		
		//Check totals
		$this->assertEquals(100, $response["ranking"][0]["total"]["points"]);
		$this->assertEquals(60, $response["ranking"][0]["total"]["penalty"]); /* 60 because contest started 60 mins ago in the default factory */
		
		// Check places
		$this->assertEquals(1, $response["ranking"][0]["place"]);
		$this->assertEquals(1, $response["ranking"][1]["place"]);
		$this->assertEquals(3, $response["ranking"][2]["place"]);
		
		// Check data per problem
		$this->assertEquals(100, $response["ranking"][0]["problems"][$problemData["request"]["alias"]]["points"]);
		$this->assertEquals(60, $response["ranking"][0]["problems"][$problemData["request"]["alias"]]["penalty"]);
		$this->assertEquals(0, $response["ranking"][0]["problems"][$problemData["request"]["alias"]]["wrong_runs_count"]);
	}
	
	/**
	 * Set 0% of scoreboard for contestants, should show all 0s
	 */
	public function testScoreboardPercentajeForContestant() {
		
		DAO::$useDAOCache = false;
		// Get a problem
		$problemData = ProblemsFactory::createProblem();

		// Get a contest 
		$contestData = ContestsFactory::createContest();
		
		// Set 0% of scoreboard show
		ContestsFactory::setScoreboardPercentage($contestData, 0);		

		// Add the problem to the contest
		ContestsFactory::addProblemToContest($problemData, $contestData);

		// Create our contestant
		$contestant = UserFactory::createUser();
		
		// Create a run
		$runData = RunsFactory::createRun($problemData, $contestData, $contestant);
		
		// Grade the run
		RunsFactory::gradeRun($runData);
		
		// Create request
		$r = new Request();
		$r["contest_alias"] = $contestData["request"]["alias"];
		$r["auth_token"] = $this->login($contestant);
		
		// Create API
		$response = ContestController::apiScoreboard($r);								
		
		// Validate that we have ranking
		$this->assertEquals(1, count($response["ranking"]));
		
		$this->assertEquals($contestant->getUsername(), $response["ranking"][0]["username"]);
		
		//Check totals
		$this->assertEquals(0, $response["ranking"][0]["total"]["points"]);
		$this->assertEquals(0, $response["ranking"][0]["total"]["penalty"]); /* 60 because contest started 60 mins ago in the default factory */
		
		// Check data per problem
		$this->assertEquals(0, $response["ranking"][0]["problems"][$problemData["request"]["alias"]]["points"]);
		$this->assertEquals(0, $response["ranking"][0]["problems"][$problemData["request"]["alias"]]["penalty"]);
		$this->assertEquals(0, $response["ranking"][0]["problems"][$problemData["request"]["alias"]]["wrong_runs_count"]);
	}
	
	/**
	 * Set 0% of scoreboard for admins
	 */
	public function testScoreboardPercentajeForContestAdmin() {
		
		DAO::$useDAOCache = false;
		// Get a problem
		$problemData = ProblemsFactory::createProblem();

		// Get a contest 
		$contestData = ContestsFactory::createContest();
		
		// Set 0% of scoreboard show
		ContestsFactory::setScoreboardPercentage($contestData, 0);		

		// Add the problem to the contest
		ContestsFactory::addProblemToContest($problemData, $contestData);

		// Create our contestant
		$contestant = UserFactory::createUser();
		
		// Create a run
		$runData = RunsFactory::createRun($problemData, $contestData, $contestant);
		
		// Grade the run
		RunsFactory::gradeRun($runData);
		
		// Create request
		$r = new Request();
		$r["contest_alias"] = $contestData["request"]["alias"];
		$r["auth_token"] = $this->login($contestData["director"]);
		
		// Create API
		$response = ContestController::apiScoreboard($r);								
		
		// Validate that we have ranking
		$this->assertEquals(1, count($response["ranking"]));
		
		$this->assertEquals($contestant->getUsername(), $response["ranking"][0]["username"]);
		
		//Check totals
		$this->assertEquals(100, $response["ranking"][0]["total"]["points"]);
		$this->assertEquals(60, $response["ranking"][0]["total"]["penalty"]); /* 60 because contest started 60 mins ago in the default factory */
		
		// Check data per problem
		$this->assertEquals(100, $response["ranking"][0]["problems"][$problemData["request"]["alias"]]["points"]);
		$this->assertEquals(60, $response["ranking"][0]["problems"][$problemData["request"]["alias"]]["penalty"]);
		$this->assertEquals(0, $response["ranking"][0]["problems"][$problemData["request"]["alias"]]["wrong_runs_count"]);
	}
	
	
	/**
	 * Scoreboard merge basic test
	 */
	public function testScoreboardMerge() {
		
		// Get a problem
		$problemData = ProblemsFactory::createProblem();

		// Get contests
		$contestData = ContestsFactory::createContest();
		$contestData2 = ContestsFactory::createContest();

		// Add the problem to the contest
		ContestsFactory::addProblemToContest($problemData, $contestData);
		ContestsFactory::addProblemToContest($problemData, $contestData2);

		// Create our contestants
		$contestant = UserFactory::createUser();
		$contestant2 = UserFactory::createUser();
		
		// Create a run
		$runData = RunsFactory::createRun($problemData, $contestData, $contestant);
		$runData2 = RunsFactory::createRun($problemData, $contestData, $contestant2);
		$runData3 = RunsFactory::createRun($problemData, $contestData2, $contestant2);
		
		// Grade the run
		RunsFactory::gradeRun($runData);
		RunsFactory::gradeRun($runData2);
		RunsFactory::gradeRun($runData3);
		
		// Create request
		$r = new Request();
		$r["contest_aliases"] = $contestData["request"]["alias"] . "," . $contestData2["request"]["alias"];
		$r["auth_token"] = $this->login($contestant);		
				
		// Call API
		$response = ContestController::apiScoreboardMerge($r);								
		
		$this->assertEquals(200, $response["ranking"][0]["total"]["points"]);
		$this->assertEquals(100, $response["ranking"][1]["total"]["points"]);
		$this->assertEquals(0, $response["ranking"][1]["contests"][$contestData2["request"]["alias"]]["points"]);
	}
	
	/**
	 * Basic tests for shareable scoreboard url
	 */
	public function testScoreboardUrl() {
		
		// Get a private contest with 0% of scoreboard show percentage
		$contestData = ContestsFactory::createContest(null, 0);
		ContestsFactory::setScoreboardPercentage($contestData, 0);
		
		// Create problem
		$problemData = ProblemsFactory::createProblem();
		ContestsFactory::addProblemToContest($problemData, $contestData);
	
		// Create our user not added to the contest
		$externalUser = UserFactory::createUser();
		
		// Create our contestant, will submit 1 run
		$contestant = UserFactory::createUser();		
		
		ContestsFactory::addUser($contestData, $contestant);		
		$runData = RunsFactory::createRun($problemData, $contestData, $contestant);
		RunsFactory::gradeRun($runData);
				
		// Get the scoreboard url by using the MyList api being the
		// contest director
		$response = ContestController::apiMyList(new Request(array(
			"auth_token" => $this->login($contestData["director"])
		)));	
		
		// Look for our contest from the list and save the scoreboard tokens
		$scoreboard_url = null;
		$scoreboard_admin_url = null;
		foreach ($response["results"] as $c) {			
			if ($c["alias"] === $contestData["request"]["alias"]) {
				$scoreboard_url = $c["scoreboard_url"];
				$scoreboard_admin_url = $c["scoreboard_url_admin"];
				break;
			}
		}
		$this->assertNotNull($scoreboard_url);					
		$this->assertNotNull($scoreboard_admin_url);					
		
		// Call scoreboard api from the user
		$scoreboardResponse = ContestController::apiScoreboard(new Request(array(
			"auth_token" => $this->login($externalUser),
			"contest_alias" => $contestData["request"]["alias"],
			"token" => $scoreboard_url
		)));
				
		$this->assertEquals("0", $scoreboardResponse["ranking"][0]["total"]["points"]);		
		
		// Call scoreboard api from the user with admin token
		$scoreboardResponse = ContestController::apiScoreboard(new Request(array(
			"auth_token" => $this->login($externalUser),
			"contest_alias" => $contestData["request"]["alias"],
			"token" => $scoreboard_admin_url
		)));
				
		$this->assertEquals("100", $scoreboardResponse["ranking"][0]["total"]["points"]);
	}
	
	/**
	 * Test invalid token
	 * 
	 * @expectedException ForbiddenAccessException
	 */
	public function testScoreboardUrlInvalidToken() {
		
		// Create our user not added to the contest
		$externalUser = UserFactory::createUser();
		
		// Get a contest with 0% of scoreboard show percentage
		$contestData = ContestsFactory::createContest();
		
		// Call scoreboard api from the user
		$scoreboardResponse = ContestController::apiScoreboard(new Request(array(
			"auth_token" => $this->login($externalUser),
			"contest_alias" => $contestData["request"]["alias"],
			"token" => "invalid token"
		)));
	}
}

