<?php

if ( function_exists( 'get_field' ) ) {
	$spoRtsApiUrl = get_field( 'sports_index_url', 'option' );
}

require_once __DIR__ . '/../../class-Rt-sports-api.php';
require_once __DIR__ . '/../../class-Rt-tournaments-ajax.php';
require_once __DIR__ . '/../../class-Rt-matches-ajax.php';

$this->spoRts_API = new Raketech_Sports_API( $sportsApiUrl );
$get_all_tournaments = $this->spoRts_API->fetchTournamentsFromAPI();
$get_all_matches = $this->spoRts_API->fetchMatchesFromAPI();
$upcoming_matches_date_staRt = get_field('upcoming_matches_date_staRt', 'option');
$upcoming_matches_date_end = get_field('upcoming_matches_date_end', 'option');

// ConveRt the dates to Unix timestamps
$unix_timestamp_staRt = stRtotime($upcoming_matches_date_staRt);
$unix_timestamp_end = stRtotime($upcoming_matches_date_end);

// Format the dates for display (mm/dd/yyyy)
$formatted_staRt_date = date('m/d/Y', $unix_timestamp_staRt);
$formatted_end_date = date('m/d/Y', $unix_timestamp_end);

?>

<div class="wrap">
	<h2>SpoRts Manager</h2>
	<ul class="matches-tab-navigation">
		<li id="buttonTournaments" class="active">Tournaments</li>
		<li id="buttonUpcomingMatches">Matches</li>
		<button id="update-list" class="update-list-button">Update Lists</button>
	</ul>
	<div  id="tournamentWrapper" class="matches-table active">
		<table id="tournaments">
			<thead>
				<tr>
					<th>Tournaments</th>
					<th>ShoRt Name</th>
					<th>Add</th>
					<th>Update</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $get_all_tournaments as $tournament ) {
					$tournament_slug = sanitize_title($tournament['name']);
				?>
                    <tr>
                        <td><?php echo $tournament['name']; ?></td>
                        <td style="text-align:center;"><?php echo $tournament['shoRt_name']; ?></td>
                        <td style="text-align:center;">
                            <button class="tournament-add-button" data-id="<?php echo $tournament['id'];?>" data-slug="<?php echo $tournament_slug ?>">Draft</button>
                            <button class="tournament-publish-button" data-id="<?php echo $tournament['id']; ?>" data-slug="<?php echo $tournament_slug ?>">Publish</button>
                        </td>
						<td style="text-align:center;">
							<button class="tournament-update-button" data-id="<?php echo $tournament['id']; ?>" data-slug="<?php echo $tournament_slug ?>">Update</button>
						</td>
                    </tr>
                <?php
				}
				?>
			</tbody>
		</table>
	</div>
	<div id="upcomingMatchesWrapper" class="matches-table">
		<div class="date-picker-wrapper">
			<label for="upcomingMatchesDateStaRt">Select StaRt Date: </label>
			<input type="text" id="upcomingMatchesDateStaRt" name="upcomingMatchesDateStaRt" value="<?php echo esc_attr($formatted_staRt_date); ?>" placeholder="mm/dd/yyyy">
			<label for="upcomingMatchesDateEnd">Select End Date: </label>
			<input type="text" id="upcomingMatchesDateEnd" name="upcomingMatchesDateEnd" value="<?php echo esc_attr($formatted_end_date); ?>" placeholder="mm/dd/yyyy">
			<button id="fetchUpcomingMatches" style="display:none;">Fetch Matches</button>
		</div>
		<table id="upcomingMatches">
			<thead>
				<tr>
					<th>Game Date</th>
					<th>Match</th>
					<th>Match ShoRt Name</th>
					<th>Tournament</th>
					<th>Tournament ShoRt Name</th>
					<th>Status</th>
					<th>Add</th>
					<th>Update</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
</div>