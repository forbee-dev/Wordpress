jQuery(document).ready(async function () {
  const fetchedSlugs = await fetchCustomPostTypeSlugs();
  //console.log("Fetched slugs:", fetchedSlugs);
  jQuery("#slots").DataTable({
    processing: true,
    serverside: true,
    pageLength: 50,
    drawCallback: function (settings) {
      applyButtonLogic(fetchedSlugs);
    },
  });

  const fetchedProviders = await fetchProviders();
  jQuery("#providers").DataTable({
    pageLength: 50,
    drawCallback: function (settings) {
      applyButtonLogicProviders(fetchedProviders);
    },
  });
});

jQuery(document).ready(function ($) {
  $("#update-list").click(function () {
    $.ajax({
      type: "POST",
      url: slots_manager.ajax_url,
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
