<?php

if ( function_exists( 'get_field' ) ) {
	$sportsApiUrl = get_field( 'sports_index_url', 'option' );
}

require_once __DIR__ . '/../../class-rt-sports-api.php';
require_once __DIR__ . '/../../class-rt-tournaments-ajax.php';
require_once __DIR__ . '/../../class-rt-matches-ajax.php';

$this->sports_API = new Rt_Sports_API( $sportsApiUrl );
$get_all_tournaments = $this->sports_API->fetchTournamentsFromAPI();
$get_all_matches = $this->sports_API->fetchMatchesFromAPI();

?>

<div class="wrap">
	<h2>Sports Manager</h2>
	<ul class="matches-tab-navigation">
		<li id="buttonTournaments" class="active">Tournaments</li>
		<li id="buttonNewMatches" class="">Matches</li>
		<button id="update-list" class="update-list-button">Update Lists</button>
	</ul>
	<div  id="tournamentWrapper" class="matches-table active">
		<table id="tournaments">
			<thead>
				<tr>
					<th>Tournaments</th>
					<th>Add</th>
					<th>Update</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $get_all_tournaments as $tournament ) {
					$tournament_slug = sanitize_title($tournament->name);
				?>
                    <tr>
                        <td><?php echo $tournament->name; ?></td>
                        <td style="text-align:center;">
                            <button class="tournament-add-button" data-id="<?php echo $tournament->id;?>" data-slug="<?php echo $tournament_slug ?>">Draft</button>
                            <button class="tournament-publish-button" data-id="<?php echo $tournament->id; ?>" data-slug="<?php echo $tournament_slug ?>">Publish</button>
                        </td>
						<td style="text-align:center;">
							<button class="tournament-update-button" data-id="<?php echo $tournament->id; ?>" data-slug="<?php echo $tournament_slug ?>">Update</button>
						</td>
                    </tr>
                <?php
				}
				?>
			</tbody>
		</table>
	</div>
    	<div id="matchesWrapper" class="matches-table">
		<table id="matches">
			<thead>
				<tr>
					<th>Match</th>
					<th>Sport</th>
					<th>Tournament</th>
					<th>Add</th>
					<th>Update</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $get_all_matches as $match ) {
					$match_name = $match->name . ' - ' . $match->sub_title;
					$match_slug = sanitize_title($match_name);
				?>
					<tr>
						<td><?php echo $match_name; ?></td>
						<td class="td-center"><?php echo $match->sport; ?></td>
						<td><?php echo $match->tournament->name; ?></td>
						<td class="td-center">
							<button class="match-add-button" data-key="<?php echo $match->key ?>" data-slug="<?php echo $match_slug ?>">Draft</button>
							<button class="match-publish-button" data-key="<?php echo $match->key ?>" data-slug="<?php echo $match_slug ?>">Publish</button>
						</td>
						<td class="td-center">
							<button class="match-update-button" data-key="<?php echo $match->key ?>" data-slug="<?php echo $match_slug ?>">Update</button>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>