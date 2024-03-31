// DataTable format with request info and Action custom column
// Custom column has a div and dropdown included
let table = new DataTable('#inventory-table', {
    ajax: 'api.php',
    columns: [
        {data: null, render: (data, type, row, meta) => {
            return `<div><a href="javascript:$('#actionSpan-${row.req_id}').slideToggle()">⚙</a></div>
            <div id="actionSpan-${row.req_id}" class="collapse">
                <a href="javascript:editRequestAction(${row.req_id})" class="edit-link">📝 Edit</a>
                <a href="javascript:deleteRequestAction(${row.req_id})" class="delete-link">❌ Delete</a>
            </div>
            `;
        }},
        {'data': 'requested_by'},
        {'data': 'items'},
        {'data': 'item_type'},

    ]
});

// Select Item HTML Template
var selectBlock = `
<p>
    <select class="form-select requestForm__select" name="items[]" onchange="requestFormValidateItems()" required></select>
    <span onclick="requestFormRemoveSelect(this)">❌ Remove item</span>
</p>
`;

// Adds new select options accoding same item types and using current values (for edit form)
function requestFormAddItemSelect(values = ['']) {
    
    let newSelectBlock = [];
    // values has the values for edit form, it will create a select for each given value
    for (let j = 0; j < values.length; j++) {
        // Create a copy of selectBlock template to add options
        newSelectBlock[j] = $((' ' + selectBlock).slice(1));

        // Selects Area to add new elements
        let selects = $('#requestFormSelectsArea select');

        // Uses item_type from first select to get only same type
        let item_type;

        // Extract from VALUE in first select or set to 0 if empty
        if (selects.length > 0 && (selects.eq(0).val() !== null && selects.eq(0).val() !== undefined)) {
            item_type = parseInt(selects.eq(0).val().split(',')[1]);
        } else {
            item_type = 0;
        }

        // Removes 'Remove item' option if is the first select, we don't want to remove all items!
        if (selects.length == 0) {
            newSelectBlock[j].find('span').remove();
        }
        
        console.log('Item type selected: ' + item_type);

        // Gets item list with same item_type and creates the new selects
        $.ajax({
            url: 'api.php?action=items&type=' + item_type,
            method: 'GET',
            async: false,
            success: function(data) {
                data = data.data;
                // For each item, is created an option with value and text
                for (let i = 0; i < data.length; i++) {
                    let optionValue = `{${data[i].id},${data[i].item_type}}`;
                    let optionText = `${data[i].item}`;
            
                    // Filling options with desired item type and added to draft selectBlock
                    let option = $('<option>').val(optionValue).text(optionText);
                    $(newSelectBlock[j]).find('select').append(option);
            
                }
                // If a value is specified for edit sets the value
                if (values[j] != '') {
                    $(newSelectBlock[j]).find('select').val(values[j]);
                }
                // Adds the select finished to selects Area
                $('#requestFormSelectsArea').append(newSelectBlock[j]);
                console.log(' ¿No?');
                return true;
            }
        })
    }

}

// Prepares the Add Form with title, a hidden input for web service
function addRequestAction() {
    $('#requestFormModalLabel').text(`Add new request`);
    $('#requestActionInput').val('add');
    $('#requestIdInput').val('');
    
    // Cleans all the selects before create new ones and set user to empty
    $('#requestFormSelectsArea').html('');
    $('#requestFormUserInput').val('');
    requestFormAddItemSelect();

    // Shows the modal
    $('#requestFormModal').modal('show');
    
}

// Prepares the Edit Form with title, a hidden input for web service
function editRequestAction(requestId) {

    // Updates title, and hidden requestId
    $('#requestFormModalLabel').text(`Edit request ID ${requestId}`);
    $('#requestActionInput').val('edit');
    $('#requestIdInput').val(requestId);

    // Cleans all the selects before create new ones using request data
    $('#requestFormSelectsArea').html('');
    // Ajax get request data
    $.ajax({
        url: 'api.php?id=' + requestId,
        method: 'GET',
        success: function(data) {

            data = data.data;
            // Sets user field from database
            $('#requestFormUserInput').val(data['requested_by']);

            // Sends item values selected to create selects with options
            requestFormAddItemSelect(data['items']);

            // Shows the modal
            $('#requestFormModal').modal('show');
            return true;

        }
    })

}

// Pop ups a delete confirmation
function deleteRequestAction(requestId) {
    
    // If confirms, goes to deleteConfirmed(id)
    $('#deleteConfirmationModal .modal-body').text(`Please confirm deletion of request ${requestId}`);
    $('#confirmDeleteButton').attr('onclick', `deleteConfirmed(${requestId})`);
    $('#deleteConfirmationModal').modal('show');
}

// Confirmation here deleting with ajax
function deleteConfirmed(requestId) {
    $('#deleteConfirmationModal').modal('hide');

    $.ajax({
        url: 'api.php?id=' + requestId,
        method: 'DELETE',
        success: function(response) {
            console.log('Deleted ' + requestId);
            // Sets text and show for alert message
            $('#alertBlock div')
                .attr('class', 'alert alert-success')
                .text('Request deleted successfully.');

            // Reloads results
            table.ajax.reload();
        } ,
        error: function(error) {
            console.log('Error deleting request, please try again.');
            // Sets text and show for alert message
            $('#alertBlock div')
                .attr('class', 'alert alert-danger')
                .text('Error deleting request, please try again.');
        }
    });

    // Hides Delete modal
    $('#requestFormModal').modal('hide');
    
}

// Validates if any select has different item_type and delete the select if not
function requestFormValidateItems(element) {

    let selects = $('#requestFormSelectsArea select');

    // Extracts type from first select value
    if (selects.eq(0).val() !== null && selects.eq(0).val() !== undefined) {
        item_type = parseInt(selects.eq(0).val().split(',')[1]);
    } else {
        item_type = 0;
    }

    // Validate if other selects has different item types and remove it
    for (let i = 0; i < selects.length; i++) {
        if (item_type != parseInt(selects.eq(i).val().split(',')[1])) {
            selects.eq(i).parent('p').remove();
        }
    }

}

// Handles button for remove select for item
function requestFormRemoveSelect(trigger) {
    let element = $(trigger);

    console.log(element.parent('p'));
    element.parent('p').remove();
    console.log('Select removed');
}

// Preparing FormData for modal, onsubmit
$(document).ready(function () {
    $('#requestForm').submit(function (e) {
        e.preventDefault();
        console.log('Submit');

        // Gets the form from jQuery selector
        let formData = new FormData($('#requestForm')[0]);

        // If action is add, sends formData to webservice without aditional process (because is not json class)
        if (formData.get('action') == 'add') {
            $.ajax({
                url: 'api.php',
                data: formData,
                method: 'POST',
                contentType: false,
                processData: false,
                success: function(response) {
                    console.log('Request added successfully');
                    console.log(response);
                    $('#alertBlock div')
                        .attr('class', 'alert alert-success')
                        .text('Request added successfully.');
                    // Reloads results
                    table.ajax.reload();

                } ,
                error: function(error) {
                    console.log('Error adding request, please try again.');
                    console.log(error);
                    $('#alertBlock div')
                        .attr('class', 'alert alert-danger')
                        .text('Error adding request, please try again.');
                }
            });

            // Hides form modal
            $('#requestFormModal').modal('hide');
            
        // If action is edit, makes the same that add, but with specific url and alert messages
        } else if (formData.get('action') == 'edit' && formData.get('req_id') != 0) {

            $.ajax({
                url: 'api.php?id=' + formData.get('req_id'),
                data: formData,
                method: 'POST',
                contentType: false,
                processData: false,
                success: function(response) {
                    console.log('Request edited successfully');
                    console.log(response);
                    $('#alertBlock div')
                        .attr('class', 'alert alert-success')
                        .text('Request edited successfully.');
                    // Reloads results
                    table.ajax.reload();

                } ,
                error: function(error) {
                    console.log('Error editing request, please try again.');
                    console.log(error);
                    $('#alertBlock div')
                        .attr('class', 'alert alert-danger')
                        .text('Error editing request, please try again.');
                }
            });

            // Hides form modal
            $('#requestFormModal').modal('hide');

        }
    })
})
