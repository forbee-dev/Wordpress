jQuery(document).ready(function ($) {
  // Listener for changes on the toplists_market select
  $("#acf-field_65cb8cce75bc6").change(function () {
    let market = $(this).val();
    $.ajax({
      type: "POST",
      url: myAjax.ajaxurl,
      data: {
        action: "update_toplists_field",
        toplists_market: market,
        security: myAjax.nonce,
      },
      success: function (response) {
        if (response.success) {
          // Clear existing options
          let select = $("#acf-field_65cb8d3c75bc7");
          select.empty();
          // Append the default "Select Toplist" option
          select.append($("<option></option>").attr("value", "").text("Select Toplist"));

          // Append new options
          $.each(response.data, function (key, value) {
            select.append($("<option></option>").attr("value", key).text(value));
          });
        } else {
          // Handle error or no data case
          alert("Failed to load toplists or no data available.");
        }
      },
      error: function () {
        alert("Failed to load toplists.");
      },
    });
  });

  // Listener for changes on the toplists select
  $("#acf-field_65cb8d3c75bc7").change(function () {
    let toplistId = $(this).val();
    $.ajax({
      type: "POST",
      url: myAjax.ajaxurl,
      data: {
        action: "update_toplist_json_field",
        toplist_id: toplistId,
        security: myAjax.nonce,
      },
      success: function (response) {
        if (response.success) {
          // Display the toplist JSON data
          $("#acf-field_65cb91cc75bc8").val(response.data.toplist_data_json);
        } else {
          // Handle failure
          alert(response.data.message || "Failed to update toplist data.");
        }
      },
      error: function () {
        alert("Failed to update toplist data.");
      },
    });
  });
});
