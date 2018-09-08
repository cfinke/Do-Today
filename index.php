<?php

date_default_timezone_set( "America/Los_Angeles" );

class Chore {
	var $name;
	var $last_completed;
	var $frequency_number;
	var $frequency_interval;
	var $time_remaining;
	
	public function __construct( $chore_data ) {
		$this->name = $chore_data['name'];
		$this->last_completed = $chore_data['last_completed'];
		$this->frequency_interval = $chore_data['frequency_interval'];
		$this->frequency_number = $chore_data['frequency_number'];
		
		$this->time_remaining = $this->last_completed + ( $this->frequency_interval_in_seconds( $this->frequency_interval ) * $this->frequency_number ) - time();
	}
	
	public function last_completed_fuzzy() {
		$time_since = time() - $this->last_completed;
		
		if ( $time_since < 60 ) {
			return "less than a minute ago";
		}
		else if ( $time_since < 90 * 60 ) {
			return max( round( $time_since / 60 ), 2 ) . " minutes ago";
		}
		else if ( $time_since < 24 * 60 * 60 ) {
			return max( round( $time_since / 60 / 60 ), 2 )  . " hours ago";
		}
		else if ( $time_since < 30 * 24 * 60 * 60 ) {
			return max( round( $time_since / 60 / 60 / 24 ), 2 ) . " days ago";
		}
		else if ( $time_since < 100 * 24 * 60 * 60 ) {
			return max( round( $time_since / 60 / 60 / 24 / 7 ), 2 ) . " weeks ago";
		}
		else {
			return max( round( $time_since / 60 / 60 / 24 / 30 ), 2 ) . " months ago";
		}
	}
	
	public function time_remaining_fuzzy() {
		if ( $this->time_remaining < 60 * 60 ) {
			return "Now";
		}
		else if ( $this->time_remaining < 23 * 60 * 60 ) {
			$hours_left = round( $this->time_remaining / 60 / 60 );
			
			return "In " . $hours_left . " hour" . ( $hours_left > 1 ? "s" : "" );
		}
		else if ( $this->time_remaining < 6 * 24 * 60 * 60 ) {
			$days_left = round( $this->time_remaining / 24 / 60 / 60 );
			
			return "In " . $days_left . " day" . ( $days_left > 1 ? "s" : "" );
		}
		else if ( $this->time_remaining < 90 * 24 * 60 * 60 ) {
			$weeks_left = round( $this->time_remaining / 7 / 24 / 60 / 60 );
			
			return "In " . $weeks_left . " week" . ( $weeks_left > 1 ? "s" : "" );
		}
		else {
			$months_left = round( $this->time_remaining / 30 / 24 / 60 / 60 );
			
			return "In " . $months_left . " month" . ( $months_left > 1 ? "s" : "" );
		}
	}
	
	public function urgency() {
		$time_between = ( $this->frequency_interval_in_seconds( $this->frequency_interval ) * $this->frequency_number );
		$time_until = $this->time_remaining;
		
		if ( $time_until < 0 ) {
			return 0;
		}
		
		return max( 1, round( ( $time_until / $time_between ) * 5 ) );
	}
	
	private function frequency_interval_in_seconds( $interval ) {
		switch ( $interval ) {
			case 'year':
				return 365 * 24 * 60 * 60;
			break;
			case 'month':
				return 30 * 24 * 60 * 60;
			break;
			case 'week':
				return 7 * 24 * 60 * 60;
			break;
			case 'day':
				return 24 * 60 * 60;
			break;
			case 'hour':
				return 60 * 60;
			break;
			case 'minute':
				return 60;
			break;
		}
		
		return 0;
	}
	
	public function due() {
		$seconds_left_today = strtotime( date( "Y-m-d 00:00:00", strtotime( "+1 day" ) ) ) - time();
		
		if ( $this->time_remaining < 0 ) {
			return 'overdue';
		}
		else if ( $this->time_remaining < $seconds_left_today ) {
			return 'today';
		}
		else if ( $this->time_remaining < $seconds_left_today + ( 24 * 60 * 60 ) ) {
			return 'tomorrow';
		}
		else {
			return 'future';
		}
	}
}

function sort_chores( $a, $b ) {
	if ( $a->time_remaining > $b->time_remaining ) {
		return 1;
	}
	else if ( $a->time_remaining < $b->time_remaining ) {
		return -1;
	}
	
	return 0;
}

function get_json_filename() {
	return basename( $_SERVER['PHP_SELF'], ".php" ) . ".json";
}

function get_chores_from_json() {
	if ( file_exists( get_json_filename() ) ) {
		$chores_json = file_get_contents( get_json_filename() );

		if ( $chores_json ) {
			$chores = json_decode( $chores_json, true );

			return $chores;
		}
	}
	
	return array();
}

function save_chores_json( $chores_json ) {
	return file_put_contents( get_json_filename(), json_encode( $chores_json ) );
}

if ( isset( $_POST['add_chore'] ) ) {
	$chores_json = get_chores_from_json();
	
	$chores_json[ $_POST['name'] ] = array(
		'frequency_number' => $_POST['frequency_number'],
		'frequency_interval' => $_POST['frequency_interval'],
		'last_completed' => time(),
	);
	
	save_chores_json( $chores_json );
	
	header( "Location: ?" . $_SERVER['QUERY_STRING'] );
	exit;
}
else if ( isset( $_POST['complete_chore'] ) ) {
	$chores_json = get_chores_from_json();
	
	$chores_json[ $_POST['chore'] ]['last_completed'] = time();
	
	save_chores_json( $chores_json );
	
	header( "Location: ?" . $_SERVER['QUERY_STRING'] );
	exit;
}
else if ( isset( $_POST['delete_chore'] ) ) {
	$chores_json = get_chores_from_json();
	
	unset( $chores_json[ $_POST['chore'] ] );
	
	save_chores_json( $chores_json );
	
	header( "Location: ?" . $_SERVER['QUERY_STRING'] );
	exit;
}

$chores = get_chores_from_json();

foreach ( $chores as $chore_name => $chore ) {
	$chore['name'] = $chore_name;
	$chores[ $chore_name ] = new Chore( $chore );
}

usort( $chores, 'sort_chores' );

?>
<!doctype html>
<html>
	<head>
		<title>Chores</title>
		<meta charset="UTF-8" />
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta name="viewport" content="user-scalable=no,width=device-width" />
		<link rel="icon" type="image/png" href="img/icon-180.png?mtime=<?php echo filemtime( "img/icon-180.png"); ?>" />
		<link rel="apple-touch-icon" sizes="180x180" href="img/icon-180.png?mtime=<?php echo filemtime( "img/icon-180.png"); ?>" />
		<link rel="stylesheet" type="text/css" href="css/reset.css?mtime=<?php echo filemtime( "css/reset.css" ); ?>" />
		<link rel="stylesheet" type="text/css" href="css/chores-core.css?mtime=<?php echo filemtime( "css/chores-core.css" ); ?>" />
	</head>
	<body>
		<?php if ( isset( $_GET['admin'] ) ) { ?>
			<div class="due due-nothing">
				<h2>Add Chore</h2>
				
				<div class="chore add-chore">
					<form method="post" action="" class="add">
						<input type="submit" name="add_chore" value="Add Chore" />
						<p>
							Remind me to <input type="text" name="name" /> every <input type="number" name="frequency_number" size="3" />
							<label>
								<select name="frequency_interval">
									<option value="year">Years</option>
									<option value="month">Months</option>
									<option value="week">Weeks</option>
									<option value="day" selected="selected">Days</option>
									<option value="hour">Hours</option>
									<option value="minute">Minutes</option>
								</select>
							</label>
						</p>
					</form>
				</div>
			</div>
		<?php } ?>
		<?php if ( ! empty( $chores ) ) {
			$last_due = '';
			
			if ( $chores[0]->due() != 'overdue' && $chores[0]->due() != 'today' ) {
				echo '<div class="due due-today">';
				echo '<h2>All Done for Today!</h2>';
				echo '</div>';
			}
			
			foreach ( $chores as $chore ) {
				if ( $chore->due() != $last_due ) {
					if ( $last_due ) {
						echo '</div>';
					}
				
					echo '<div class="due due-' . $chore->due() . '">';
					echo '<h2>' . ucfirst( $chore->due() ) . '</h2>';
					
					
					$last_due = $chore->due();
				}

				?>
				<div class="chore urgency-<?php echo $chore->urgency(); ?>">
					<?php if ( isset( $_GET['admin'] ) ) { ?>
						<form method="post" action="" class="delete">
							<input type="hidden" name="chore" value="<?php echo htmlspecialchars( $chore->name ); ?>" />
							<input type="submit" name="delete_chore" value="X" />
						</form>
					<?php } ?>
					<form method="post" action="" class="done">
						<input type="hidden" name="chore" value="<?php echo htmlspecialchars( $chore->name ); ?>" />
						<input type="submit" name="complete_chore" value="✓" />
					</form>
					<p class="name"><?php echo htmlspecialchars( $chore->name ); ?></p>
					<p class="time_remaining"><em><?php echo htmlspecialchars( $chore->time_remaining_fuzzy() ); ?></em></p>
					<p class="last_completed">Done <?php echo htmlspecialchars( $chore->last_completed_fuzzy() ); ?></p>
				</div>
			<?php } ?>
			</div>
		<?php } ?>
		<script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
		<script type="text/javascript" src="js/chores-core.js?mtime=<?php echo filemtime( "js/chores-core.js" ); ?>"></script>
	</body>
</html>