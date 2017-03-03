<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Reef Encounter</title>
	
</head>
<body>
	<form method="post" action="ReefEncounter.php">
		<input type="submit" name="addShrimp" value="Add Shrimp">
		<input type="submit" name="resetBoard" value="Reset Board">
		<input type="submit" name="saveGame" value = "Save Game">
	</form>
	
	<a href="GameLog7.txt" target="_blank">View Game Log</a>
	<a href="SavedGame7.txt" target="_blank">View Saved Game</a>

	<?php
		session_start();
		if (isset($_SESSION['saveGame'])){
			ob_start();
		}
		
		if (isset($_REQUEST['addShrimp']))
			$_SESSION['action'] = "addShrimp";
		
		$action = getAction();		
				
		const SHRIMP_NONE = 0;
		const PTILE_NONE = 0;
		const PTILE_WHITE = 1;
		const PTILE_YELLOW = 2;
		const PTILE_ORANGE = 4;
		const PTILE_PINK = 8;
		const PTILE_GREY = 16;
		const SHRIMP_BLUE = "32";
		const SHRIMP_GREEN = "64";
		const SHRIMP_PURPLE = "128";
		const SHRIMP_RED = "256";				

		const ALGA_BLUE = 0;
		const ALGA_GREEN = 1;
		const ALGA_PURPLE = 2;
		const ALGA_RED = 3;

		function getFilename($id) {
			switch ($id) {
				case PTILE_WHITE: return "p0.jpg";
				case PTILE_YELLOW: return "p1.jpg";
				case PTILE_ORANGE: return "p2.jpg";
				case PTILE_PINK: return "p3.jpg";
				case PTILE_GREY: return "p4.jpg";
				case SHRIMP_BLUE: return "sB.gif";
				case SHRIMP_GREEN: return "sG.gif";
				case SHRIMP_PURPLE: return "sP.gif";
				case SHRIMP_RED: return "sR.gif";
				default: return "";
			}
		}
				
		class CoralTile {
			private $state;
			private $strong;
			private $weak;
			
			private $alga1;
			private $alga2;
			
			private $locked;
			
			function __construct($strong, $weak, $algatop, $algabot) {
				$this->state = 1;
			
				$this->strong = $strong;
				$this->weak = $weak;
				
				$this->algatop = $algatop;
				$this->algabot = $algabot;
				
				$this->locked = false;
			}
			
			function flip() {
				$this->state = $this->state == 1 ? 2 : 1;
			
				$temp = $this->strong;
				$this->strong = $this->weak;
				$this->weak = $temp;
				
				$temp = $this->algatop;
				$this->algatop = $this->algabot;
				$this->algabot = $temp;
			}
			
			function getStrong() { 
				return $this->strong;
			}
			
			function getWeak() {
				return $this->weak;
			}
			
			function getAlga() {
				return $this->algatop;
			}
			
			function isLocked() {
				return $this->locked;
			}
			
			function display() {
				echo "			
				<img src='RE/" . getFilename($this->strong) . "' alt='tile:" . $this->strong . "' height='32' width='32' style='display:block; float:left;'</img>
				<img src='RE/" . getFilename($this->strong) . "' alt='tile:" . $this->strong . "' height='32' width='32' style='display:block; float:left;'</img>
				<img src='RE/ct" . $this->algatop . $this->algabot . ".gif' alt='coral tile:" . $this->algatop . $this->algabot . "' height='32' width='32' style='display:block; float:left;'</img>
				<img src='RE/" . getFilename($this->weak) . "' alt='tile:" . $this->weak . "' height='32' width='32' style='display:block; float:left;'</img>";
			}
		}
	
		class BoardSquare {
			private $contents;			
			private $bValid;
			private $bBonus;
			
			function __construct($tile_type, $valid) {
				$this->contents = $tile_type;				
				$this->bValid = $valid;
				$this->bBonus = false;
			}			
			
			function getShrimp() {
				return $this->contents & (SHRIMP_BLUE + SHRIMP_GREEN + SHRIMP_PURPLE + SHRIMP_RED);
			}			                                
			function setShrimp($shrimp) { 			
				if ($this->contents & SHRIMP_BLUE) $this->contents ^= SHRIMP_BLUE;
				if ($this->contents & SHRIMP_GREEN) $this->contents ^= SHRIMP_GREEN;
				if ($this->contents & SHRIMP_PURPLE) $this->contents ^= SHRIMP_PURPLE;
				if ($this->contents & SHRIMP_RED) $this->contents ^= SHRIMP_RED;
				$this->contents |= $shrimp;
			}
			
			function isBonus() {
				return $this->bBonus;
			}
			
			function isValid() {
				return $this->bValid;
			}
			
			function getTile() {
				return $this->contents & (PTILE_WHITE + PTILE_YELLOW + PTILE_ORANGE + PTILE_PINK + PTILE_GREY);
			} 
			function setTile($tile) {
				if ($this->contents & PTILE_WHITE) $this->contents ^= PTILE_WHITE;
				if ($this->contents & PTILE_YELLOW) $this->contents ^= PTILE_YELLOW;
				if ($this->contents & PTILE_ORANGE) $this->contents ^= PTILE_ORANGE;
				if ($this->contents & PTILE_PINK) $this->contents ^= PTILE_PINK;
				if ($this->contents & PTILE_GREY) $this->contents ^= PTILE_GREY;
				$this->contents |= $tile;
			}
			
			function removeShrimp() {
				if ($this->contents & SHRIMP_BLUE) $this->contents ^= SHRIMP_BLUE;
				if ($this->contents & SHRIMP_GREEN) $this->contents ^= SHRIMP_GREEN;
				if ($this->contents & SHRIMP_PURPLE) $this->contents ^= SHRIMP_PURPLE;
				if ($this->contents & SHRIMP_RED) $this->contents ^= SHRIMP_RED;
			}
			
			function makeBonus() {
				$this->bBonus = true;
			}
			
			function getContents() {
				return $this->contents;
			}
			
			function setContents($data) {
				$this->contents = $data;
			}
		}
	
		class Board {
			public $board;
			public $id;
			
			function __construct($board, $id) {
				$this->board = $board;
				$this->id = $id;
			}
			
			function setBonus($r, $c) {
				$this->board[$r][$c]->makeBonus();
			}
						
			function display($action = "") {
				echo "
		<div style='position:relative; float:left;'>
		<img src='RE/b" . $this->id . ".jpg' alt='board:" . $this->id . "'</img>";
				
				for ($i = 0; $i < count($this->board); $i++) {
					for ($j = 0; $j < count($this->board[$i]); $j++) {
						if ($i == 0) {
							$val = chr(($this->id - 1) * 7 + $j + 65);
							echo "					
		<span style='top:" . ($i * 40 + 2) . "px; left:" . ($j * 40 + 44) . "px; width:32px; height:32px; position:absolute; z-index:200; font-weight:bold'>
			$val
		</span>";						
						}
						if ($j == 0) {
							$val = $i + 1;
							echo "					
		<span style='top:" . ($i * 40 + 35) . "px; left:" . ($j * 40 + 2) . "px; width:32px; height:32px; position:absolute; z-index:200; font-weight:bold'>
			$val
		</span>";			
						}
						if ($this->board[$i][$j]->getTile() != PTILE_NONE) {
							echo "
		<span style='top:" . ($i * 40 + 27) . "px; left:" . ($j * 40 + 32) . "px; width:32px; height:32px; position:absolute; z-index:200; border: solid 2px #0000AF;'>
			<img src='RE/" . getFilename($this->board[$i][$j]->getTile()) . "' alt='tile:" . getFilename($this->board[$i][$j]->getTile()) . "' height='32' width='32'</img>
		</span>";
						}
						if ($this->board[$i][$j]->getShrimp() != SHRIMP_NONE) {
						echo "
		<span style='top:" . ($i * 40 + 27) . "px; left:" . ($j * 40 + 32) . "px; width:32px; height:32px; position:absolute; z-index:300; border: solid 2px #0000AF;'>
							<img src='RE/" . getFilename($this->board[$i][$j]->getShrimp()) . "' alt='shrimp:" . getFilename($this->board[$i][$j]->getShrimp()) . "' height='32' width='32'</img>
		</span>";
						}
						if ($action == "addShrimp" && $this->canPlaceShrimp($i, $j)){
							echo "
		<span style='top:" . ($i * 40 + 27) . "px; left:" . ($j * 40 + 32) . "px; width:32px; height:32px; position:absolute; z-index:300;'>
			<a href='ReefEncounter.php?b" . $this->id . $i . $j . "=" . SHRIMP_RED . "'  
				style='text-decoration:none; text-align:center; display:block; vertical-align:middle; line-height:32px'>[+]</a>
		</span>";
						}
		//						else if ($this->board[$i][$j]->isValid()) {								
		//							echo "
		//		<span style='top:" . ($i * 40 + 27) . "px; left:" . ($j * 40 + 32) . "px; width:32px; height:32px; position:absolute; z-index:200;'>
		//			<a href='ReefEncounter.php?b" . $this->id . $i . $j . "=" . PTILE_YELLOW . "'  
		//				style='text-decoration:none; text-align:center; display:block; vertical-align:middle; line-height:32px'>[+]</a>
		//		</span>";
		//						}
					}	
				}
				echo "
		</div>";
			}
			
			function canPlaceShrimp($i, $j) {
				if ($this->board[$i][$j]->isValid()){
					if ($this->board[$i][$j]->getShrimp() != SHRIMP_NONE)
						return false;
					return true;
				}
				else {
					return false;
				}
			}
		}
	
		class Player {
			
		}
		
		function init_boards() {
			$_SESSION['board1'] = new Board(
				array(
					array(new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, false)),
					array(new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_GREY, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, false)),
					array(new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_YELLOW, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true)),
					array(new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_WHITE, true), new BoardSquare(PTILE_NONE, true)),
					array(new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_ORANGE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_PINK, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true)),
					array(new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, false)),
				)
				, 1
			);
			$_SESSION['board1']->setBonus(3, 3);
			
			$_SESSION['board2'] = new Board(
				array(
					array(new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true)),
					array(new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_ORANGE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, false)),
					array(new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_WHITE, true), new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_YELLOW, true), new BoardSquare(PTILE_NONE, true)),
					array(new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true)),
					array(new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_PINK, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_GREY, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true)),
					array(new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, false), new BoardSquare(PTILE_NONE, true), new BoardSquare(PTILE_NONE, true)),			
				)
				, 2
			);
			$_SESSION['board2']->setBonus(3, 3);
		}
		
		$g_coral_tiles = array(
			new CoralTile(PTILE_WHITE, 	PTILE_YELLOW, 	ALGA_GREEN, 	ALGA_BLUE),
			new CoralTile(PTILE_WHITE, 	PTILE_ORANGE, 	ALGA_RED, 		ALGA_PURPLE),
			new CoralTile(PTILE_YELLOW, PTILE_ORANGE, 	ALGA_BLUE, 		ALGA_RED),
			new CoralTile(PTILE_YELLOW, PTILE_PINK, 	ALGA_PURPLE, 	ALGA_BLUE),
			new CoralTile(PTILE_ORANGE, PTILE_PINK, 	ALGA_GREEN, 	ALGA_RED),
			new CoralTile(PTILE_ORANGE, PTILE_GREY, 	ALGA_BLUE, 		ALGA_GREEN),
			new CoralTile(PTILE_PINK, 	PTILE_WHITE, 	ALGA_PURPLE, 	ALGA_GREEN),
			new CoralTile(PTILE_PINK, 	PTILE_GREY, 	ALGA_GREEN, 	ALGA_PURPLE),
			new CoralTile(PTILE_GREY, 	PTILE_WHITE, 	ALGA_RED, 		ALGA_PURPLE),
			new CoralTile(PTILE_GREY, 	PTILE_YELLOW, 	ALGA_BLUE, 		ALGA_RED)
		);		
		
		function getAction() {
			$ret = "";
			if (isset($_SESSION['action'])){
				$ret = $_SESSION['action'];
				unset($_SESSION['action']);
			}
			return $ret;
		}	

		
		if (isset($_REQUEST['resetBoard']))
			$_SESSION['resetBoard'] = true;
		if (isset($_SESSION['resetBoard'])){
			init_boards();
			$fp = fopen("./GameLog7.txt", "w");	
			fclose($fp);
			unset($_SESSION['resetBoard']);
		}		
		
		if (!isset($_SESSION['INIT_BOARD'])){
			$_SESSION['INIT_BOARD'] = 0;
			init_boards();
		}
		else {
			//update board			
			if (isset($_SESSION['board1']) && isset($_SESSION['board2'])) {
$fp = fopen("./GameLog7.txt", "a");			
				for ($i = 1; $i <= 2; $i++){
					for ($j = 0; $j < count($_SESSION['board1']->board); $j++){
						for ($k = 0; $k < count($_SESSION['board1']->board[$j]); $k++){
							$t = "b" . $i . $j . $k;
							if (isset($_REQUEST[$t])) {
								if ($_REQUEST[$t] > 16 && $_SESSION{"board" . $i}->board[$j][$k]->getShrimp() != $_REQUEST[$t]) {
									$_SESSION{"board" . $i}->board[$j][$k]->setShrimp($_REQUEST[$t]);
fwrite($fp, "Placed shrimp at: " . chr(($i - 1) * 7 + $k + 65) . ($j + 1) . "\n");
								}
								else if ($_REQUEST[$t] <= 16 && $_SESSION{"board" . $i}->board[$j][$k]->getTile() != $_REQUEST[$t])
									$_SESSION{"board" . $i}->board[$j][$k]->setTile($_REQUEST[$t]);						
							}
						}
					}
				}
fclose($fp);				
			}
		}
		
		//DISPLAY GAMEBOARD
		echo "
	<div id='gameboard'>";
		
		echo "
		<div style='position:absolute; left:90px; top:400px; float:left;'>";		
		for ($i = 0; $i < count($g_coral_tiles); $i++) {
			echo "
			<span style='top:0px; left:" . $i * 68 . "px; width:64px; height:64px; position:absolute; z-index:100; border: solid 1px #000000;'>";
			$g_coral_tiles[$i]->display();
			echo "
			</span>";
		}
		echo "
		</div>";
	
		if (isset($_SESSION['board1'])) {
			$_SESSION['board1']->display($action);
			$_SESSION['board2']->display($action);
		}
		
		echo "
	</div>";		
		//END DISPLAY GAMEBOARD
		
		//print_r($_REQUEST);
		
		//if (isset($_SESSION))
		//	print_r($_SESSION);
	
	if (isset($_REQUEST['saveGame'])){
		$fp = fopen("./SavedGame7.txt", "w");		
		for ($i = 1; $i <= 2; $i++){
			fwrite($fp, "board" . $i . "\n");
			for ($j = 0; $j < count($_SESSION['board1']->board); $j++){				
				for ($k = 0; $k < count($_SESSION['board1']->board[$j]); $k++){
					$t = "b" . $i . $j . $k;										
					fwrite($fp, $_SESSION{"board" . $i}->board[$j][$k]->getShrimp() + $_SESSION{"board" . $i}->board[$j][$k]->getTile() . "\t");
				}
				fwrite($fp, "\n");
			}
			fwrite($fp, "\n\n");
		}
		fclose($fp);
	}
	
	?>
</body>
</html>