"use strict";

const state = {
    open: false,
    deleting: false,
    button: null,
};
const modal = $("#delete_modal");
const form = $("#delete_form");

const resetCardAndState = function () {
    const card = state.button.closest(".card");
    card.find("a").removeClass("disabled_deleting");
    Object.assign(state, {
        open: false, deleting: false, button: null,
    });
    return card;
};

const success = function () {
    const {button} = state;
    const card = resetCardAndState();
    const message = $(document.createElement("span"));
    message.addClass("success_message").text("Success");
    button.next().replaceWith(message);
    if (form.data("show")) {
        window.location = form.data("show");
    } else {
        setTimeout(function () {
            card.fadeOut(500, function () {
                card.next("br").remove();
                card.remove();
            });
        }, 1000);
    }
};
const error = function (xhr, statusText, error) {
    const {button} = state;
    resetCardAndState();
    const message = $(document.createElement("span"));
    message.addClass("failure_message").text("Error: " + error);
    button.next().replaceWith(message);
};

modal.on("hide.bs.modal", function () {
    if (!state.open || state.deleting || state.button == null) {
        return;
    }
    Object.assign(state, {
        open: false, deleting: false, button: null,
    });
});

$("button.delete-modal").on("click", function (event) {
    event.preventDefault();
    if (state.open || state.deleting || state.button != null) {
        return;
    }
    modal.modal("show");
    Object.assign(state, {
        open: true, deleting: false, button: $(this),
    });
});

$("#delete_button").on("click", function () {
    if (!state.open || state.deleting || state.button == null) {
        return;
    }
    const url = form.attr("action");
    const {button} = state;
    form.find("[name='id']").val(button.data("id"));
    button.nextAll().remove();
    button.after(`
<div class="spinner-grow text-danger" role="status">
  <span class="sr-only">Deleting...</span>
</div>`,
    );
    button.closest(".card").find("a").addClass("disabled_deleting");
    modal.modal("hide");

    $.ajax({
        url,
        type: "POST",
        data: form.serialize(),
        success,
        error,
    });

    Object.assign(state, {
        open: false, deleting: true, button,
    });
});
