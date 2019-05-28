"use strict";

$("#state").addClass("selectpicker");

const assignees = $("#assignees");
assignees.hide();
$("label[for='assignees']").removeAttr("for");

assignees.after(`
<p>To delete an assignee, just leave their row empty.</p>
<table id="assignee_table" class="table">
    <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
        </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
        <tr>
            <td colspan="3">
                <button class="btn btn-primary">Add new</button>
            </td>
        </tr>
    </tfoot>
</table>
`);

const makeRow = function() {
  return `
<tr>
    <td>
        <input type="text" class="name-field form-control" />
    </td>
    <td>
        <input type="text" class="email-field form-control" />
    </td>
</tr>
`;
};

const table = $("#assignee_table");
const body = table.find("tbody");
table.find("button").on("click", function(event) {
  event.preventDefault();
  body.append(makeRow());
});

{
  const text = assignees.val();
  const re = /([^\n]+)\n([^\n]+)(?:\n\n)?/g;
  for (let match; match = re.exec(text);) {
    const { 1: name, 2: email } = match;
    body.append(makeRow());
    const { 0: nameInput, 1: emailInput } = body.find("input").slice(-2);
    nameInput.value = name;
    emailInput.value = email;
  }
}

document.getElementById("edit_form").addEventListener("submit", function(event) {
  event.preventDefault();
  event.stopImmediatePropagation();
  const names = body.find("input.name-field");
  const emails = body.find("input.email-field");
  const result = Array.from(
    names,
    (name, i) => `${name.value}\n${emails[i].value}`
  );
  assignees.val(result.join("\n\n"));
  this.submit();
}, { once: true });

