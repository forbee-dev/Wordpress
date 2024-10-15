<?php
if (!defined('ABSPATH')) {
    die('-1');
}

require_once get_template_directory() . '/helpers/transients.php';
$transients = HCMS_Transients::get_all_transients();

// Handle the deletion of the transient
if (isset($_GET['delete_transient'])) {
    $transient_to_delete = base64_decode($_GET['delete_transient']);
    delete_transient(str_replace('_transient_', '', $transient_to_delete));
    // Redirect to avoid resubmission on page refresh
    echo '<script>window.location="'.remove_query_arg("delete_transient").'"</script>';
}

// Handle the deletion of all transients
if (isset($_GET['delete_all_transients'])) {
    HCMS_Transients::delete_all_transients();
    // Redirect to avoid resubmission on page refresh
    echo '<script>window.location="'.remove_query_arg("delete_all_transients").'"</script>';
}
?>


<div id="transients_hcms_dashboard">
    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    <table class="widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th class="manage-column column-columnname" scope="col">Name</th>
                <th class="manage-column column-columnname" scope="col"></th>
            </tr>
        </thead>

        <tfoot>
            <tr>
                <th class="manage-column column-columnname" scope="col"></th>
                <th class="manage-column column-columnname" scope="col"></th>
            </tr>
        </tfoot>

        <tbody>
            <?php for ($i = 0; $i < count($transients); $i++) : ?>
                <tr class=<?php echo $i % 2 ?: "alternate" ?>>
                    <th class="check-column"><?php echo $transients[$i]->option_name ?></th>
                    <td class="column-columnname">
                        <a class="button" href="<?php echo esc_url(add_query_arg('delete_transient', base64_encode($transients[$i]->option_name))); ?>">Delete</a>
                    </td>
                </tr>
            <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr>
                <th class="manage-column column-columnname" scope="col"></th>
                <th class="manage-column column-columnname" scope="col">
                    <a class="button" href="<?php echo esc_url(add_query_arg('delete_all_transients', 'true')); ?>">Delete All</a>
                </th>
            </tr>
        </tfoot>
    </table>
</div>