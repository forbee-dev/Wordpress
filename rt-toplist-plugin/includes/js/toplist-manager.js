jQuery(document).ready(function ($) {
  // Check if we're on the specific ACF options page
  const currentPageUrl = window.location.href;
  const targetPageSlug = "toplist-manager";

  if (currentPageUrl.indexOf(targetPageSlug) !== -1) {
    // Create the button
    const submitButton = $("<button/>", {
      text: "Add/Update Toplist",
      id: "custom-add-toplist",
      class: "button button-primary",
      type: "button",
      click: function (e) {
        e.preventDefault();

        let toplistsValue = acf.getField("field_65cb8d3c75bc7").val();
        let toplistJsonValue = acf.getField("field_65cb91cc75bc8").val();

        let rowExists = false;
        let $rows = $(".acf-row");

        $rows.each(function () {
          let $row = $(this);
          let currentIdValue = $row.find(".acf-field-65cbb4a6d6d47 input").val();

          // Check if this row's saved_toplist_id matches toplistsValue
          if (currentIdValue === toplistsValue) {
            // Update this row's toplist_json value
            $row.find(".acf-field-65cbb4b9d6d48 textarea").val(toplistJsonValue);
            rowExists = true;
            return false; // Break the loop
          }
        });

        // If toplistsValue does not exist, add a new row
        if (!rowExists) {
          // Populate the new row after a shoRt delay
          const $newLastRow = $(".acf-row:last");
          $newLastRow.find(".acf-field-65cbb4a6d6d47 input").val(toplistsValue);
          $newLastRow.find(".acf-field-65cbb4b9d6d48 textarea").val(toplistJsonValue);
          $(".acf-repeater-add-row").click();
        }
      },
    });

    // Append the button to the options page
    $(".acf-save-button").first().append(submitButton);
  }
});