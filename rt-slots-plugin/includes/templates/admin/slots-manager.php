<?php

if ( function_exists( 'get_field' ) ) {
	$slotApiUrl = get_field( 'slot_library_url', 'option' );
	$postType = get_field( 'post_type', 'option' );
}

require_once __DIR__ . '/../../class-rt-slots-api.php';
require_once __DIR__ . '/../../class-rt-slots-ajax.php';
require_once __DIR__ . '/../../class-rt-providers-ajax.php';

$this->slots_API = new Raketech_Slots_API( $slotApiUrl );
$get_all_slots = $this->slots_API->getSlots();
$get_all_providers = $this->slots_API->getProviders();


?>

<div class="wrap">
	<h2>Slot Manager</h2>
	<ul class="slots-tab-navigation">
		<li id="buttonProviders" class="active">Providers</li>
		<li id="buttonNewSlots" class="">Slots</li>
		<button id="update-list" class="update-list-button">Update Lists</button>
	</ul>
	<div  id="providerWrapper" class="slots-table active">
		<table id="providers">
			<thead>
				<tr>
					<th>Provider</th>
					<th>Add</th>
					<th>Update</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $get_all_providers as $provider ) {
				?>
                    <tr>
                        <td><?php echo $provider->name; ?></td>
                        <td style="text-align:center;">
                            <button class="provider-add-button" data-id="<?php echo $provider->id; ?>" data-slug="<?php echo $provider->slug; ?>">Add</button>
                        </td>
						<td style="text-align:center;">
							<button class="provider-update-button" data-id="<?php echo $provider->id; ?>" data-slug="<?php echo $provider->slug; ?>">Update</button>
						</td>
                    </tr>
                <?php
				}
				?>
			</tbody>
		</table>
	</div>
    	<div id="slotsWrapper" class="slots-table">
		<table id="slots">
			<thead>
				<tr>
					<th>Slot</th>
					<th>Provider</th>
					<th>Date Added</th>
					<th>Date Updated</th>
					<th>Add</th>
					<th>Update</th>
					<th>Offline Desktop</th>
					<th>Offline Mobile</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $get_all_slots as $slot ) {
				?>
					<tr>
						<td><?php echo $slot->title; ?></td>
						<td><?php echo $slot->provider; ?></td>
						<td><?php echo $slot->date_added; ?></td>
						<td><?php echo $slot->date_updated; ?></td>
						<td>
							<button class="slot-add-button" data-slug="<?php echo $slot->slug; ?>">Draft</button>
							<button class="slot-publish-button" data-slug="<?php echo $slot->slug; ?>">Publish</button>
						</td>
						<td>
							<button class="slot-update-button" data-slug="<?php echo $slot->slug; ?>">Update</button>
						</td>
						<td class="offline-checkbox">
							<input type="checkbox" class="slot-offline-desktop" data-slug="<?php echo $slot->slug; ?>">
						</td>
						<td class="offline-checkbox">
							<input type="checkbox" class="slot-offline-mobile" data-slug="<?php echo $slot->slug; ?>">
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>