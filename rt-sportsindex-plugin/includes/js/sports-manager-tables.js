jQuery(document).ready(async function () {
  const fetchedSlugs = await fetchCustomPostTypeSlugs();
  jQuery("#matches").DataTable({
    pageLength: 50,
    drawCallback: function (settings) {
      applyButtonLogic(fetchedSlugs);
    },
  });
  const fetchedTournaments = await fetchCustomPostTypeSlugs();
  jQuery("#tournaments").DataTable({
    pageLength: 50,
    drawCallback: function (settings) {
      applyButtonLogicTournaments(fetchedTournaments);
    },
  });
});
jQuery(document).ready(function ($) {
  $("#update-list").click(function () {
    $.ajax({
      type: "POST",
      url: sports_manager.ajax_url,
      data: {
        action: "update_list",
      },
      success: function (response) {
        console.log(response);
        location.reload(); // Reloads the page after the AJAX call is successful
      },
      error: function () {
        alert("Error");
      },
    });
  });
});