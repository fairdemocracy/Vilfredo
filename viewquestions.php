<?php

$headcommands='
<link rel="stylesheet" href="js/jquery/tooltip/jquery.tooltip.css" />
<link rel="stylesheet" href="tabs.css" />

<title>VgtA: Questions</title>';

require_once('header.php');

//set_log('viewquestions.php USERID = '.$userid);

#$userid=isloggedin();
//if ($userid)
//{
?>

<script type="text/javascript" src="js/jquery-1.6.min.js"></script>
<script src="js/jquery/jquery.bgiframe.js" type="text/javascript"></script>
<script src="js/jquery/jquery.dimensions.js" type="text/javascript"></script>
<script src="js/jquery/tooltip/jquery.tooltip.js" type="text/javascript"></script>
<script src="js/jquery/tooltip/chili-1.7.pack.js" type="text/javascript"></script>
<script type="text/javascript" src="js/jquery/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="js/jquery/jquery.livequery.js"></script>
<script type="text/javascript" src="/js/sprintf-0.6.js"></script> 
<script type="text/javascript" src="/js/cookies/jquery.cookie.js"></script>
<script type="text/javascript" src="/js/underscore.js"></script> 
<script type="text/javascript" src="js/jquery/jquery.bgiframe.min.js"></script>
<script type="text/javascript" src="js/jquery/RichTextEditor/jqDnR.min.js"></script>
<script type="text/javascript" src="js/jquery/jquery.jqpopup.min.js"></script>
<script type="text/javascript" src="js/vilfredo.php"></script>

<script type="text/javascript">
var cookieexpires = 3; //days
$(function() {
	$(".foottip a[tooltip]").tooltip({
		bodyHandler: function() {
			return $($(this).attr("tooltip")).html();
		},
		showURL: false
	});

});
</script>
<?php
// Prepare room query param if set
$room_param = CreateNewQuestionURL();

// Move dormant questions to final voting.
AutoMoveDormantToFinalVoting();

?>

<div class="">
<div class="centerbox">
<div class="newquestionbox">
<h3><a href="newquestion.php<?php echo $room_param?>"><?=$VGA_CONTENT['ask_quest_txt']?></a></h3>
</div>
</div>
<div class="clearboth">&nbsp;</div>
</div>


<div class="panels">
<div class="panel">
<div class="centerbox">


<?php
	
	// **
	// Set question filter
	// **
	$question_filter = GetQuestionFilterOpen($userid);

	
	// *****
	// NEW QUESTIONS
	// ****

	$sql = "SELECT questions.id, questions.title, questions.roundid, questions.phase, users.username, users.id, questions.question, questions.room FROM questions, users WHERE questions.phase = 0 AND questions.roundid = 1 AND users.id = questions.usercreatorid " . $question_filter . " ORDER BY questions.id DESC LIMIT 50";
	$response = mysql_query($sql);
	$newquestionswritten=0;
	while ($row = mysql_fetch_array($response))
	{
		$phase=$row[3];
		$generation=$row[2];
		$thatusername=$row[4];
		$thatuserid=$row[5];
		$proposalsid=$row[6];
		$questionid=$row[0];
		$room=$row[7];

		$urlquery = CreateQuestionURL($questionid, $room);

		$nactualproposals = CountProposals($questionid,$generation);

		// No proposals means the question is new
		if($nactualproposals == 0)
		{
			if (!$newquestionswritten)
			{
				echo '<div class="newquestionbox">';
				echo "<h2>{$VGA_CONTENT['new_quest_txt']}</h2><p>";
			}

			$newquestionswritten=$newquestionswritten+1;
			echo '<p>';

			if ($thatuserid==$userid)
			{
				echo '<b>';
			}
			
			echo '<fieldset class="foottip">';
			echo '<a href="http://www.flickr.com/photos/found_drama/1023671528/"><img src="images/germinating.jpg" title="' . $VGA_CONTENT['new_quest_title'] . '" height=42 ></a> <a title="' . $VGA_CONTENT['new_quest_exp_title'] . '" href="viewquestion.php' . $urlquery . '" tooltip="#footnote' . $row[0] . '" >' . $row[1] . '</a> ';

			$UserString=WriteUserVsReader($thatuserid,$userid);

			echo RenderQIconInfo($UserString, $room);
			echo '</td></tr></table>';

			echo '<div class="invisible" id="footnote' . $row[0] . '">' . $VGA_CONTENT['new_quest_exp_title'] . '<br/>' . $VGA_CONTENT['question_label'] . ' '.$row[6].'.</div>';
			echo '</fieldset>';
			if ($thatuserid==$userid)
			{
				echo '</b>';
			}
			echo '</p>';
		}
		
	}
	if ($newquestionswritten)
	{
		echo '</div>'; // New questions box
		
#		echo '<p>No New Vilfredo Questions for this Room</p>';
	}
	

	echo '</div><!-- centerbox -->';
	echo '<div class="clearboth">&nbsp;</div>';
	
	// *****
	// PROPOSING
	// ****


	echo '<div class="leftfloatbox">';
	echo '<div class="proposingbox">';

	echo '<h2> <img src="images/writing.jpg" height=48> ' . $VGA_CONTENT['propose_ans_txt'] . '</h2><p>';

	$sql = "SELECT questions.id, questions.title, questions.roundid, questions.phase, 
	users.username, users.id, MAX(proposals.id) AS latest, 
	questions.question, questions.minimumtime, questions.maximumtime, questions.room 
	FROM questions, users, proposals 
	WHERE questions.phase = 0 AND users.id = questions.usercreatorid AND questions.evaluation_phase = 'evaluation'
	AND proposals.experimentid = questions.id " . $question_filter . " 
	GROUP BY questions.id ORDER BY latest DESC";
	$response = mysql_query($sql);
	
	while ($row = mysql_fetch_array($response))
	{
		echo '<p>';
		$phase=$row[3];
		$generation=$row[2];
		$thatusername=$row[4];
		$thatuserid=$row[5];
		$proposalsid=$row[6];
		$questionid=$row[0];
		$minimumtime=$row[8];
		$maximumtime=$row[9];
		$room=$row[10];

		$urlquery = CreateQuestionURL($questionid, $room);

		$nrecentproposals = CountProposals($questionid,$generation);
		$nrecentendorsers = CountEndorsers($questionid,$generation-1);
		$nAuthorsNewProposals = count(AuthorsOfNewProposals($questionid,$generation));
		
		if($nAuthorsNewProposals == 0)
		{
			$pastgeneration = $generation -1;
			$sql2 = "SELECT id, source FROM proposals 
			WHERE experimentid = ".$questionid." AND roundid = ".$pastgeneration." 
			AND dominatedby = 0";
			$response2 = mysql_query($sql2);
			$row2 = mysql_fetch_array($response2);

			if($row2)
			{
				if ($nrecentendorsers == CountEndorsersToAProposal($row2[0]))
				{
					continue;
				}
			}
		}

		if ($thatuserid==$userid)
		{
			echo '<b>';
		}

		//---
		$nrecentproposals=CountProposals($questionid,$generation-1);
		$nEndorsersRecentProposals=CountEndorsers($questionid,$generation-1);
		$nrecentparetofront=count(ParetoFront($questionid,$generation-1));
		$nAuthorsNewProposals=count(AuthorsOfNewProposals($questionid,$generation));
		$nAuthorsRecentProposals=count(AuthorsOfNewProposals($questionid,$generation-1));
		$nactualproposals=CountProposals($questionid,$generation);

		if ($nrecentparetofront > 0)
		{
			$newproposalswritten=$nactualproposals-$nrecentparetofront;

			echo '<fieldset class="foottip">';
			echo '<table border=0><tr><td>';
			
			if ($userid)
			{
				if (HasThisUserProposedSomething($questionid,$generation,$userid))
				{
					echo '<img src="images/tick.jpg" height=20 title="' . $VGA_CONTENT['already_prop_title'] . '"> ';
				}
				else
				{
					echo '<img src="images/tick_empty.png" height=20 title="' . $VGA_CONTENT['not_prop_title'] . '"> ';
				}
			}
			else
			{
				echo '&nbsp;';
			}

			echo '</td><td><a href="http://www.flickr.com/photos/jphilipson/2100627902/"><img src="images/tree.jpg" title="' . $VGA_CONTENT['gen_txt'] . ' '.$generation.'" height=42 ></a></td><td><a href="viewquestion.php' . $urlquery . '"  tooltip="#footnote' . $row[0] . '" >' . $row[1] . '</a><br /> ';

				$UserString=WriteUserVsReader($thatuserid,$userid);

				echo RenderQIconInfo($UserString, $room);

				$ReadyToGo= IsQuestionReadyToBeMovedOn($questionid,$phase,$generation);
				if($ReadyToGo)
				{
					echo '<td><img src="images/clock.jpg" title="' . $VGA_CONTENT['ready_to_move_title'] . '" height=42></td>';
				}
				$ReadyToAutoGo= IsQuestionReadyToAutoMoveOn($questionid,$phase,$generation);
				if($ReadyToAutoGo)
				{
					echo '<td><img src="images/sveglia.gif" title="' . $VGA_CONTENT['auto_move_title'] . '" height=42></td>';
				}

				echo '</td></tr></table>';

			echo '<div class="invisible" id="footnote' . $row[0] . '">' . $row[7] . '.<br/><strong>Generation</strong>='.$generation.';<br/>Recently '.$nAuthorsNewProposals.' proposers, proposed '.$newproposalswritten.' solutions.<br/>There are also '.$nrecentparetofront.' inherited from the previous generation.';
			$mintime=WriteTime($minimumtime);
			$maxtime=WriteTime($maximumtime);
			echo '<br/>Minumum time: '.$mintime.';<br/> Maximum time: '.$maxtime;

			echo '</div></fieldset>';

		}
		else
		{
			echo '<fieldset class="foottip">';



			echo '<table border=0><tr><td>';
			if ($userid)
			{
				if (HasThisUserProposedSomething($questionid,$generation,$userid))
				{
					echo '<img src="images/tick.jpg" height=20 title="' . $VGA_CONTENT['already_prop_title'] . '"> ';
				}
				else
				{
					echo '<img src="images/tick_empty.png" height=20 title="' . $VGA_CONTENT['not_prop_title'] . '"> ';
				}
			}
			else
			{
				echo '&nbsp;';
			}

			echo '</td><td><a href="http://www.flickr.com/photos/found_drama/1023671528/"><img src="images/germinating.jpg" title="New Question" height=42 ></a></td><td><a href="viewquestion.php' . $urlquery . '" tooltip="#footnote' . $row[0] . '"  >' . $row[1] . '</a> <br />';

			$UserString=WriteUserVsReader($thatuserid,$userid);

			echo RenderQIconInfo($UserString, $room);
			$ReadyToGo= IsQuestionReadyToBeMovedOn($questionid,$phase,$generation);
			if($ReadyToGo)
			{
				echo '<td><img src="images/clock.jpg" title="This question is ready to be moved on. The questionair can do it at any time. If you still have not proposed your possible answers, please do so as soon as possible" height=42></td>';
			}
			$ReadyToAutoGo= IsQuestionReadyToAutoMoveOn($questionid,$phase,$generation);
			if($ReadyToAutoGo)
			{
				echo '<td><img src="images/sveglia.gif" title="This question will soon be automatically moved on. If you still have not proposed your possible answers, please do so as soon as possible" height=42></td>';
			}

			echo '</td></tr></table>';

			echo '<div class="invisible" id="footnote' . $row[0] . '">' . $row[7] . '.<br/><strong>Generation</strong>='.$generation.';<br/>Recently '.$nAuthorsNewProposals.' proposers, proposed '.$nactualproposals.' solutions.';
			$mintime=WriteTime($minimumtime);
			$maxtime=WriteTime($maximumtime);
			echo '<br/>Minumum time: '.$mintime.';<br/> Maximum time: '.$maxtime;

			echo '<br/></div></fieldset>';

		}
		//---
		if ($thatuserid==$userid)
		{
			echo '</b>';
		}

		echo '</p>';
	}
	echo '</div><!-- leftfloatbox -->';
	echo '</div><!-- poposingbox -->';
	
	
	// *****
	// ENDORSING
	// ****
		echo '<div class="rightfloatbox">';
		echo '<div class="endorsingbox">';
		
		echo '<h2> <img src="images/endorsing.jpg" height=48> ' . $VGA_CONTENT['vote_all_txt'] . '</h2><p>';
	
		$sql = "SELECT questions.id, questions.title, questions.roundid, questions.phase, 
		users.username, users.id, questions.minimumtime, questions.maximumtime, questions.room  
		FROM questions, users 
		WHERE questions.phase = 1 AND users.id = questions.usercreatorid AND questions.evaluation_phase = 'evaluation'" . $question_filter . " 
		ORDER BY questions.lastmoveon DESC, questions.roundid DESC, 
		questions.phase DESC, questions.id DESC ";
		$response = mysql_query($sql);
		
		while ($row = mysql_fetch_array($response))
		{
			echo '<p>';
			$phase=$row[3];
			$generation=$row[2];
			$thatusername=$row[4];
			$thatuserid=$row[5];
			$questionid=$row[0];
			$minimumtime=$row[6];
			$maximumtime=$row[7];
			$room=$row[8];
	
			$urlquery = CreateQuestionURL($questionid, $room);
	
			$nrecentproposals=CountProposals($questionid,$generation);
			$nrecentendorsers=CountEndorsers($questionid,$generation);
			$nAuthorsNewProposals=count(AuthorsOfNewProposals($questionid,$generation));
			$nrecentparetofront=count(ParetoFront($questionid,$generation-1));
			$newproposalswritten=$nrecentproposals-$nrecentparetofront;
	
			echo '<fieldset class="foottip">';
	
			echo '<table border=0><tr><td>';
			
			if ($userid)
			{
				if (HasThisUserEndorsedSomething($questionid,$generation,$userid))
				{
					echo '<img src="images/tick.jpg" height=20 title="you have already expressed your endorsements over here"> ';
				}
				else
				{
					echo '<img src="images/tick_empty.png" height=20 title="' . $VGA_CONTENT['not_expressed_end_title'] . '"> ';
				}
			}
			else
			{
				echo '&nbsp;';
			}
	
	
			echo '</td><td><a href="http://www.flickr.com/photos/johnfahertyphotography/2675723448/"><img src="images/flowers.jpg" title="' . $VGA_CONTENT['choose_title'] . '" height=42 ></a></td><td> <a href="viewquestion.php' . $urlquery. '" tooltip="#footnote' . $row[0] . '"   >' . $row[1] . '</a> <br />';
	
			$UserString=WriteUserVsReader($thatuserid,$userid);
	
			echo RenderQIconInfo($UserString, $room);
	
			$ReadyToGo= IsQuestionReadyToBeMovedOn($questionid,$phase,$generation);
			if($ReadyToGo)
			{
				echo '<td><img src="images/clock.jpg" title="This question is ready to be moved on. The questionair can do it at any time. If you still have not endorsed the answers you agree with, please do so as soon as possible" height=42></td>';
			}
	
			$ReadyToAutoGo= IsQuestionReadyToAutoMoveOn($questionid,$phase,$generation);
			if($ReadyToAutoGo)
			{
				echo '<td><img src="images/sveglia.gif" title="' . $VGA_CONTENT['auto_mve_soon_title'] . '" height=42></td>';
			}
	
			echo '</td></tr></table>';
	
			echo '<div class="invisible" id="footnote' . $row[0] . '">';
			echo '<ol>';
			$sql3 = "SELECT id, blurb  FROM proposals WHERE experimentid = ".$row[0]." and roundid = ".$generation." ";
			$response3 = mysql_query($sql3);
	
			while ($row3 = mysql_fetch_array($response3))
			{
				$answerid=$row3[0];
				$answertext=$row3[1];
				echo '<li>'.$answertext.'</li>';
			}
			echo '</ol>';
			echo '<strong>' . $VGA_CONTENT['gen_txt'] . '</strong>='.$generation.';<br/>' . $VGA_CONTENT['recently_txt'] . ' '.$nrecentendorsers.' ' . $VGA_CONTENT['humans_voted_txt'] . ' '.$nrecentproposals.' ' . $VGA_CONTENT['poss_sol_txt'] . '<br/>'.$newproposalswritten.' ' . $VGA_CONTENT['prod_by_txt'] . ' '.$nAuthorsNewProposals.' ' . $VGA_CONTENT['humans_txt'] . '<br/>
			' . $VGA_CONTENT['and_txt'] . ' '.$nrecentparetofront.' ' . $VGA_CONTENT['inhreited_from_txt'] . '';
			$mintime=WriteTime($minimumtime);
			$maxtime=WriteTime($maximumtime);
			echo '<br/>' . $VGA_CONTENT['min_time_txt'] . ' '.$mintime.';<br/> ' . $VGA_CONTENT['max_time_txt'] . ' '.$maxtime;
			echo '</div>';
	
			echo '</fieldset>';
	
	
			echo '</p>';
	
		}

		echo '</div>';
		echo '</div>';
	// ENDORSING END
echo '<div class="clearboth">&nbsp;</div>';



	# In Final Voting
	
	//========================================================================//
	echo '<div class="leftfloatbox">';
	echo '<div class="finalvotingbox">';
	echo '<h2><img src="images/voting_hand.png" height=48>' . 'Questions in the final voting phase' . '</h2><p>';

	$sql = "SELECT questions.id, questions.title, questions.roundid, 
		questions.phase, users.username, users.id, questions.room, questions.evaluation_phase
		FROM questions, users 
		WHERE questions.evaluation_phase = 'voting'
		AND users.id = questions.usercreatorid 
		$question_filter
		ORDER BY  questions.lastmoveon DESC, questions.id DESC";
		
	//set_log("Get voting questions: $sql");
	
	$response = mysql_query($sql);
	
	while ($row = mysql_fetch_array($response))
	{
		echo '<p>';
		$phase=$row[3];
		$generation=$row[2];
		$thatusername=$row[4];
		$thatuserid=$row[5];
		$questionid=$row[0];
		$room=$row[6];

		$urlquery = CreateQuestionURL($questionid, $room);

		$nrecentproposals=CountProposals($questionid,$generation);
		$nrecentendorsers=CountEndorsers($questionid,$generation-1);
		$nAuthorsNewProposals=count(AuthorsOfNewProposals($questionid,$generation));
		$nrecentparetofront=count(ParetoFront($questionid,$generation-1));
		
		if ($row['evaluation_phase'] == 'voting')
		{
			$num_winning_proposals = getParetoCountForQuestion($questionid);
			
			echo '<fieldset class="foottip">';
			echo '<table border=0 class="unanimity"><tr><td><img src="images/vote_icon.jpg" title="Final Voting Answer" height=42 ></td><td class="unanimity_info"><a href="viewvotingresults.php' . $urlquery . '" tooltip="#footnote' . $row[0] . '">' . $row[1] . '</a> ';

					$UserString=WriteUserVsReader($thatuserid,$userid);
					echo RenderQIconInfo($UserString, $room) . '<br />';
					echo '</td></tr></table>';
					echo '<div class="invisible" id="footnote' . $row[0] . '">';
						
					echo '<ol>';

					echo '<li>'.$num_winning_proposals.' proposals to vote on'.'</li>';
					echo '</ol>';
					echo '</div>';
					echo '</fieldset>';
						
		}
		echo '</p>';
	}
	 echo '</div>';
	 echo '</div>';
	//========================================================================//
	
	
	
	# Solved by Final Voting
	//========================================================================//
	echo '<div class="rightfloatbox">';
	echo '<div class="solvedfinalvotingbox">';
	echo '<h2><img src="images/voting-hands.jpg" height=48>' . 'Questions solved by final voting' . '</h2><p>';
	//echo "<p>{$VGA_CONTENT['reopen_txt']}</p>";

	$sql = "SELECT questions.id, questions.title, questions.roundid, 
		questions.phase, users.username, users.id, questions.room, questions.evaluation_phase
		FROM questions, users 
		WHERE questions.evaluation_phase = 'closed'
		AND users.id = questions.usercreatorid 
		$question_filter
		ORDER BY  questions.lastmoveon DESC, questions.id DESC";
		
	//set_log("Get finished questions: $sql");
	
	$response = mysql_query($sql);
	
	while ($row = mysql_fetch_array($response))
	{
		echo '<p>';
		$phase=$row[3];
		$generation=$row[2];
		$thatusername=$row[4];
		$thatuserid=$row[5];
		$questionid=$row[0];
		$room=$row[6];

		$urlquery = CreateQuestionURL($questionid, $room);

		$nrecentproposals=CountProposals($questionid,$generation);
		$nrecentendorsers=CountEndorsers($questionid,$generation-1);
		$nAuthorsNewProposals=count(AuthorsOfNewProposals($questionid,$generation));
		$nrecentparetofront=count(ParetoFront($questionid,$generation-1));
		
		if ($row['evaluation_phase'] == 'closed')
		{
			$winning_proposal = getFinalVoteWinnerProposal($questionid);
			
			echo '<fieldset class="foottip">';
			echo '<table border=0 class="unanimity"><tr><td><img src="images/picked.jpg" title="Final Voting Answer" height=42 ></td><td class="unanimity_info"><a href="viewvotingresults.php' . $urlquery . '" tooltip="#footnote' . $row[0] . '">' . $row[1] . '</a> ';

					$UserString=WriteUserVsReader($thatuserid,$userid);
					echo RenderQIconInfo($UserString, $room) . '<br />';
					echo '</td></tr></table>';
					echo '<div class="invisible" id="footnote' . $row[0] . '">';
						
					echo '<ol>';
					$answerid = $winning_proposal['id'];
					$answertext = $winning_proposal['blurb'];
					$votes = $winning_proposal['votes'];
					set_log("ANSWER TEXT: $answertext");
					echo '<li>'.$answertext.' - '. $votes.' votes'.'</li>';
					echo '</ol>';
					echo '</div>';
					echo '</fieldset>';
						
		}
		echo '</p>';
	}
	 echo '</div>';
	 echo '</div>';
	//========================================================================//

	// *****
	// REACHED CONCENSUS
	// *****

	echo '<div class="centerbox">';
	echo '<div class="solvedbox">';
	echo '<h2><img src="images/manyhands.jpg" height=48>' . $VGA_CONTENT['unanimity_txt'] . '</h2><p>';
	echo "<p>{$VGA_CONTENT['reopen_txt']}</p>";

	$sql = "SELECT questions.id, questions.title, questions.roundid, 
		questions.phase, users.username, users.id, questions.room, questions.evaluation_phase
		FROM questions, users 
		WHERE questions.phase = 0
		AND users.id = questions.usercreatorid 
		$question_filter
		ORDER BY  questions.lastmoveon DESC, questions.id DESC";
		
	//set_log("Get finished questions: $sql");
	
	$response = mysql_query($sql);
	
	while ($row = mysql_fetch_array($response))
	{
		echo '<p>';
		$phase=$row[3];
		$generation=$row[2];
		$thatusername=$row[4];
		$thatuserid=$row[5];
		$questionid=$row[0];
		$room=$row[6];

		$urlquery = CreateQuestionURL($questionid, $room);

		$nrecentproposals=CountProposals($questionid,$generation);
		$nrecentendorsers=CountEndorsers($questionid,$generation-1);
		$nAuthorsNewProposals=count(AuthorsOfNewProposals($questionid,$generation));
		$nrecentparetofront=count(ParetoFront($questionid,$generation-1));
		
		if ($row['evaluation_phase'] == 'closed')
		{
			$winning_proposal = getFinalVoteWinnerProposal($questionid);
			
			echo '<fieldset class="foottip">';
			echo '<table border=0 class="unanimity"><tr><td><a href="http://www.flickr.com/photos/don-piefcone/395175227/"><img src="images/apple.jpg" title="Final Voting Answer" height=42 ></a></td><td class="unanimity_info"><a href="viewvotingresults.php' . $urlquery . '" tooltip="#footnote' . $row[0] . '">' . $row[1] . '</a> ';

					$UserString=WriteUserVsReader($thatuserid,$userid);
					echo RenderQIconInfo($UserString, $room) . '<br />';
					echo '</td></tr></table>';
					echo '<div class="invisible" id="footnote' . $row[0] . '">';
						
					echo '<ol>';
					$answerid = $winning_proposal['id'];
					$answertext = $winning_proposal['blurb'];
					$votes = $winning_proposal['votes'];
					set_log("ANSWER TEXT: $answertext");
					echo '<li>'.$answertext.' - '. $votes.' votes'.'</li>';
					echo '</ol>';
					echo '</div>';
					echo '</fieldset>';
						
		}
		
		// If there are no new proposals, just the pareto from previous generation
		if($nAuthorsNewProposals == 0)
		{
			// Select last gen pareto
			$pastgeneration = $generation - 1;
			$sql2 = "SELECT id, source FROM proposals 
			WHERE experimentid = ".$questionid." AND roundid = ".$pastgeneration." 
			AND dominatedby = 0";
			$response2 = mysql_query($sql2);
			$row2= mysql_fetch_array($response2);

			if($row2)
			{
				if ($nrecentendorsers == CountEndorsersToAProposal($row2['id']))
				{
					echo '<fieldset class="foottip">';
					$sql3 = "SELECT id, blurb FROM proposals 
					WHERE experimentid = ".$questionid." and roundid = ".$generation." ";
					$response3 = mysql_query($sql3);

					if (mysql_num_rows($response3) > 1)
					{
						echo '<table border=0 class="unanimity"><tr><td><a href="http://www.flickr.com/photos/lencioni/2223801603/"><img src="images/fruits.jpg" title="' . $VGA_CONTENT['agree_more_title'] . '" height=42 ></a></td><td class="unanimity_info"><a href="viewresults.php' . $urlquery . '" tooltip="#footnote' . $row[0] . '">' . $row[1] . '</a> ';
						
						$UserString=WriteUserVsReader($thatuserid,$userid);

						echo RenderQIconInfo($UserString, $room) . '<br />';
					}
					else
					{
						echo '<table border=0 class="unanimity"><tr><td><a href="http://www.flickr.com/photos/don-piefcone/395175227/"><img src="images/apple.jpg" title="Generated Answer" height=42 ></a></td><td class="unanimity_info"><a href="viewresults.php' . $urlquery . '" tooltip="#footnote' . $row[0] . '">' . $row[1] . '</a> ';

						$UserString=WriteUserVsReader($thatuserid,$userid);
						echo RenderQIconInfo($UserString, $room) . '<br />';
					}
					
					$endorsers=Endorsers($questionid,$pastgeneration);
					echo "<b>{$VGA_CONTENT['agree_fnd_txt']}</b>: ";
					
					foreach ($endorsers as $e)
					{
						$UserString=WriteUserVsReader($e,$userid);
						echo $UserString . '&nbsp;&nbsp;';
					}

					echo '</td></tr></table>';
					echo '<div class="invisible" id="footnote' . $row[0] . '">';
					echo '<ol>';

					while ($row3 = mysql_fetch_array($response3))
					{
						$answerid=$row3[0];
						$answertext=$row3[1];
						echo '<li>'.$answertext.'</li>';
					}
					echo '</ol>';
					echo '</div>';
					echo '</fieldset>';
				}
			}
		}
		echo '</p>';
	}
	 echo '</div>';
	 echo '</div>';
	
	
	 echo '<div class="clearboth"></div>';
	 ?></div>
	 </div><!-- panels -->
	<div class="clear"></div>
<?php

if (!$userid)
{
	//set_log('Not logged in - storing request');
	SetRequest();
}

include('footer.php');
?>