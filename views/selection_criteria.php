<?php
    require_once('controllers/SelectionController.php');

    $selectionController = new SelectionController();
    $facilities = $selectionController->getFacilities();
    $lotGroups = $selectionController->getLotHeaders();
    $waferGroups = $selectionController->getWaferHeaders();
    $abbrev = $selectionController->getProbingFilter();
?>

<script>
    function fetchFilters(selectedValue, targetElement, type) {
        $.ajax({
            url: 'fetch_filters.php',
            method: 'GET',
            data: {
                value: selectedValue
            },
            dataType: 'json',
            success: function(response) {
                console.log(response);
                // Create the <ul> element with the required classes
                const ul = $('<ul>', {
                    class: 'max-h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700',
                    'aria-labelledby': 'dropdownFilterButton'
                });

                // Add items from the response
                response.forEach(item => {
                    const li = $('<li>');
                    const div = $('<div>', {
                        class: 'flex items-center p-2 rounded hover:bg-gray-100'
                    });

                    const checkbox = $('<input>', {
                        id: `checkbox-item-${item}`,
                        name: `filter-${type}[]`,
                        type: 'checkbox',
                        value: item,
                        class: 'w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500'
                    });

                    const label = $('<label>', {
                        for: `checkbox-item-${item}`,
                        class: 'w-full ms-2 text-sm font-medium text-gray-900 rounded',
                        text: item
                    });

                    div.append(checkbox, label);
                    li.append(div);
                    ul.append(li);
                });

                // Clear previous content and append the new <ul>
                $(targetElement).empty().append(ul);
            },
            error: function(xhr, status, error) {
                console.error('Error:', status, error);
                console.log('Response Text:', xhr.responseText);
            }
        });
    }
</script>

<div class="container mx-auto p-6">
    <h1 class="text-center text-2xl font-bold mb-4 w-full">Selection Criteria</h1>
    <form action="table_view.php" method="GET" id="criteriaForm">
        
        
        <div class="flex w-full justify-end items-end">
            
        </div>

        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label for="facility" class="block text-sm font-medium text-gray-700">Facility</label>
                <select id="facility" name="facility[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <?php foreach ($facilities as $facility): ?>
                        <option value="<?= $facility ?>"><?= $facility ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="work_center" class="block text-sm font-medium text-gray-700">Work Center</label>
                <select id="work_center" name="work_center[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on facility selection -->
                </select>
            </div>

            <div>
                <label for="device_name" class="block text-sm font-medium text-gray-700">Device Name</label>
                <select id="device_name" name="device_name[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on work center selection -->
                </select>
            </div>

            <div>
                <label for="test_program" class="block text-sm font-medium text-gray-700">Test Program</label>
                <select id="test_program" name="test_program[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on device name selection -->
                </select>
            </div>

            <div>
                <label for="lot" class="block text-sm font-medium text-gray-700">Lot</label>
                <select id="lot" name="lot[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on test program selection -->
                </select>
            </div>

            <div>
                <label for="wafer" class="block text-sm font-medium text-gray-700">Wafer</label>
                <select id="wafer" name="wafer[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                    <!-- Options will be populated based on lot selection -->
                </select>
            </div>

            <div class="flex flex-col w-full col-span-3">
                <div class="flex w-full">
                    <label for="parameter" class="text-sm font-medium text-gray-700 block">Parameter</label>
                    <label for="parameter-x" class="text-sm font-medium text-gray-700 w-1/2 hidden">X Parameter</label>
                    <label for="parameter-y" class="text-sm font-medium text-gray-700 w-1/2 ml-6 hidden">Y Parameter</label>
                    <button id="parameter-button" type="button" class="ml-auto py-0.5 px-1.5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-gray-700 focus:z-10 focus:ring-4 focus:ring-gray-100">&#x2BC8;</button>
                </div>
                
                <div id="parameter-1" class="w-full" style="display: flex">
                    <select id="parameter" name="parameter[]" size="5" class="bg-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                        <!-- Options will be populated based on wafer selection -->    
                    </select>
                </div>
                
                <div id="parameter-2" class="w-full" style="display: none">
                    <select id="parameter-x" name="parameter-x[]" size="5" class="bg-white mt-1 block w-1/2 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                        <!-- Options will be populated based on wafer selection -->
                    </select>
                    <select id="parameter-y" name="parameter-y[]" size="5" class="bg-white mt-1 block w-1/2 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" multiple>
                        <!-- Options will be populated based on wafer selection -->
                    </select>
                </div>
            </div>
        </div>
        <div class="flex flex-row w-full mb-4 gap-4">
            
        

            
<div class="border-2 border-gray-200 rounded-lg p-4 mb-4 w-1/4">
    <h2 class="text-md italic mb-4 w-auto text-gray-500 bg-gray-50 bg-transparent text-center">Group by (x)</h2>

    <!-- Dropdown menu -->
    <div class="flex w-full justify-start items-center gap-2 mb-4">
        <button id="dropdownGroupXButton" data-dropdown-toggle="dropdownGroupX" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
            X Axis
            <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
            </svg>
        </button>

        <div id="dropdownGroupX" class="z-10 hidden w-auto h-64 overflow-y-auto bg-white divide-y divide-gray-200 rounded-lg shadow">
            <ul class="p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownGroupXButton">
                <?php foreach ($lotGroups as $group): ?>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                        <input id="checkbox-item-<?= htmlspecialchars($group) ?>" name="group-x[]" type="radio" value="<?= htmlspecialchars($group) ?>" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                        <label for="checkbox-item-<?= htmlspecialchars($group) ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded"><?= htmlspecialchars($group) ?></label>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <ul class="p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownGroupXButton">
                <?php foreach ($waferGroups as $group): ?>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                        <input id="checkbox-item-<?= htmlspecialchars($group) ?>" name="group-x[]" type="radio" value="<?= htmlspecialchars($group) ?>" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                        <label for="checkbox-item-<?= htmlspecialchars($group) ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded"><?= htmlspecialchars($group) ?></label>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Select input for sorting -->
        <label for="sort-x" class="sr-only">Sort X</label>
        <select id="sort-x" name="sort-x" class="block py-2.5 px-4 text-sm text-gray-500 bg-transparent border-0 border-b-2 border-gray-200 appearance-none focus:outline-none focus:ring-0 focus:border-gray-200 peer ml-auto">
            <option value="ASC" selected>Ascending</option>
            <option value="DESC">Descending</option>  
        </select>
    </div>

    <!-- Selected group display -->
    <div id="selectedGroup" class="text-gray-600 dark:text-gray-300 mt-4">
        <span class="font-medium">Selected Group:</span>
        <div id="selectedGroupContainer" class="mt-2 flex space-x-2 overflow-x-auto">
            <!-- Selected group will be dynamically inserted here -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropdownGroupX = document.getElementById('dropdownGroupX');
    const selectedGroupContainer = document.getElementById('selectedGroupContainer');

    function updateSelectedGroup() {
        const selectedGroup = document.querySelector('input[name="group-x[]"]:checked');
        const selectedText = selectedGroup.nextElementSibling.textContent;
        selectedGroupContainer.innerHTML = ''; // Clear current display
        if (selectedGroup) {
            const listItem = document.createElement('div');
            listItem.className = 'flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-600 rounded';
            listItem.textContent = selectedText;
            selectedGroupContainer.appendChild(listItem);
        }
        fetchFilters(selectedText, $('#dropdownXFilter'), 'x');
    }

    // Toggle dropdown visibility
    document.getElementById('dropdownGroupXButton').addEventListener('click', function () {
        dropdownGroupX.classList.toggle('hidden');
    });

    // Update selected group on radio button change
    document.querySelectorAll('input[name="group-x[]"]').forEach(radio => {
        radio.addEventListener('change', updateSelectedGroup);
    });
});
</script>


<div class="border-2 border-gray-200 rounded-lg p-4 mb-4 w-1/4">
    <h2 class="text-md italic mb-4 w-auto text-gray-500 bg-gray-50 bg-transparent text-center">Group by (y)</h2>

    <!-- Dropdown menu and sort selection -->
    <div class="flex w-full justify-start items-center gap-2 mb-4">
        <button id="dropdownGroupYButton" data-dropdown-toggle="dropdownGroupY" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
            Y Axis
            <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
            </svg>
        </button>

        <!-- Dropdown menu -->
        <div id="dropdownGroupY" class="z-10 hidden w-auto h-64 overflow-y-auto bg-white divide-y divide-gray-200 rounded-lg shadow">
            <ul class="p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownGroupXButton">
                <?php foreach ($lotGroups as $group): ?>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                        <input id="checkbox-item-<?= htmlspecialchars($group) ?>" name="group-y[]" type="radio" value="<?= htmlspecialchars($group) ?>" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                        <label for="checkbox-item-<?= htmlspecialchars($group) ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded"><?= htmlspecialchars($group) ?></label>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <ul class="p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownGroupXButton">
                <?php foreach ($waferGroups as $group): ?>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                        <input id="checkbox-item-<?= htmlspecialchars($group) ?>" name="group-y[]" type="radio" value="<?= htmlspecialchars($group) ?>" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                        <label for="checkbox-item-<?= htmlspecialchars($group) ?>" class="w-full ms-2 text-sm font-medium text-gray-900 rounded"><?= htmlspecialchars($group) ?></label>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Select input for sorting -->
        <select id="sort-y" name="sort-y" class="block py-2.5 px-4 text-sm text-gray-500 bg-transparent border-0 border-b-2 border-gray-200 appearance-none focus:outline-none focus:ring-0 focus:border-gray-200 peer ml-auto">
            <option value="ASC" selected>Ascending</option>
            <option value="DESC">Descending</option>  
        </select>
    </div>

    <!-- Selected group display -->
    <div id="selectedGroupY" class="text-gray-600 dark:text-gray-300 mt-4">
        <span class="font-medium">Selected Group:</span>
        <div id="selectedGroupYContainer" class="mt-2 flex space-x-2 overflow-x-auto">
            <!-- Selected group will be dynamically inserted here -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropdownGroupY = document.getElementById('dropdownGroupY');
    const selectedGroupYContainer = document.getElementById('selectedGroupYContainer');

    function updateSelectedGroupY() {
        const selectedGroupY = document.querySelector('input[name="group-y[]"]:checked');
        const selectedText = selectedGroupY.nextElementSibling.textContent;
        selectedGroupYContainer.innerHTML = ''; // Clear current display
        if (selectedGroupY) {
            const listItem = document.createElement('div');
            listItem.className = 'flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-600 rounded';
            listItem.textContent = selectedText;
            selectedGroupYContainer.appendChild(listItem);
        }
        fetchFilters(selectedText, $('#dropdownYFilter'), 'y');
    }

    // Toggle dropdown visibility
    document.getElementById('dropdownGroupYButton').addEventListener('click', function () {
        dropdownGroupY.classList.toggle('hidden');
    });

    // Update selected group on radio button change
    document.querySelectorAll('input[name="group-y[]"]').forEach(radio => {
        radio.addEventListener('change', updateSelectedGroupY);
    });
});
</script>
        <div class="border-2 border-gray-200 rounded-lg p-4 mb-4 w-1/4">
            <h2 class="text-md italic mb-4 w-auto text-gray-500 bg-gray-50 bg-transparent text-center">Filter</h2>

            <!-- Dropdown menu -->
            <div class="flex w-full justify-start items-center gap-2 mb-4">
                <button id="dropdownXFilterButton" data-dropdown-toggle="dropdownXFilter" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-indigo-700 rounded-lg hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800" type="button">
                    X-Filter
                    <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <div id="dropdownXFilter" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                    
                </div>

                <button id="dropdownYFilterButton" data-dropdown-toggle="dropdownYFilter" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-indigo-700 rounded-lg hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800" type="button">
                    Y-Filter
                    <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <div id="dropdownYFilter" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                    
                </div>
            </div>
        </div>


            <div class="border-2 border-gray-200 rounded-lg p-4 mb-4 w-1/4">
                <h2 class="text-md italic mb-4 w-auto text-gray-500 bg-gray-50 bg-transparent text-center">Type of Chart</h2>
                <div class="flex flex-col w-full justify-start gap-2 ml-auto">
                    <div class="flex items-center">
                        <input id="radio-1" type="radio" value="line" name="type" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                        <label for="radio-1" class="ms-2 text-sm font-medium text-gray-900">Line Chart</label>
                    </div>
                    <div class="flex items-center">
                        <input id="radio-2" type="radio" value="scatter" name="type" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                        <label for="radio-2" class="ms-2 text-sm font-medium text-gray-900">XY Scatter Plot</label>
                    </div>
                    <div class="flex items-center">
                        <input id="radio-2" type="radio" value="cp" name="type" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                        <label for="radio-2" class="ms-2 text-sm font-medium text-gray-900">Cumulative Probability Graph</label>
                    </div>
                </div>
            </div>

        </div>
        <div class="text-center w-full flex justify-start gap-4">
            <button type="button" id="executeButton" class="bg-blue-500 text-white px-4 py-2 rounded">Execute</button>
            <button type="button" id="resetButton" class="px-4 py-2 bg-red-500 text-white rounded">Reset</button>
        </div>
    </form>
</div>

<script>
// document.getElementById('select-all').addEventListener('change', function() {
//     var checkboxes = document.querySelectorAll('.filter-checkbox');
//     for (var checkbox of checkboxes) {
//         checkbox.checked = this.checked;
//     }
// });

$(document).ready(function() {
    // Function to fetch options based on previous selection
    function fetchOptions(selectedValue, targetElement, queryType) {
        $.ajax({
            url: 'fetch_options.php',
            method: 'GET',
            data: {
                value: JSON.stringify(selectedValue),
                type: queryType
            },
            dataType: 'json',
            success: function(response) {
                let options = '';
                targetElement.html('');
                if (queryType === 'parameter') {
                    $.each(response, function(index, item) {
                        options += `<option value="${item.value}">${item.display}</option>`;
                    });
                } else {
                    $.each(response, function(index, value) {
                        options += `<option value="${value}">${value}</option>`;
                    });
                }
                targetElement.html(options);
            },
            error: function(xhr, status, error) {
                console.error('Error:', status, error);
                console.log('Response Text:', xhr.responseText);
            }
        });
    }
    
    const selectedValues = {
        Facility_ID: null,
        Work_Center: null,
        Part_Type: null,
        Program_Name: null,
        Lot_ID: null,
        Wafer_ID: null
    };

    // Event listeners for each select element
    $('#facility').change(function() {
        const selectedFacility = $(this).val();
        selectedValues.Facility_ID = selectedFacility;
        fetchOptions(selectedValues, $('#work_center'), 'work_center');
    });

    $('#work_center').change(function() {
        const selectedWorkCenter = $(this).val();
        selectedValues.Work_Center = selectedWorkCenter;
        fetchOptions(selectedValues, $('#device_name'), 'device_name');
    });

    $('#device_name').change(function() {
        const selectedDeviceName = $(this).val();
        selectedValues.Part_Type = selectedDeviceName;
        fetchOptions(selectedValues, $('#test_program'), 'test_program');
    });

    $('#test_program').change(function() {
        const selectedTestProgram = $(this).val();
        selectedValues.Program_Name = selectedTestProgram;
        fetchOptions(selectedValues, $('#lot'), 'lot');
    });

    $('#lot').change(function() {
        const selectedLot = $(this).val();
        selectedValues.Lot_ID = selectedLot;
        fetchOptions(selectedValues, $('#wafer'), 'wafer');
    });

    $('#wafer').change(function() {
        const selectedWafer = $(this).val();
        selectedValues.Wafer_ID = selectedWafer;
        fetchOptions(selectedValues, $('#parameter'), 'parameter');
    });

    $('#wafer').change(function() {
        const selectedWafer = $(this).val();
        selectedValues.Wafer_ID = selectedWafer;
        fetchOptions(selectedValues, $('#parameter-x'), 'parameter');
    });

    $('#wafer').change(function() {
        const selectedWafer = $(this).val();
        selectedValues.Wafer_ID = selectedWafer;
        fetchOptions(selectedValues, $('#parameter-y'), 'parameter');
    });

    // Reset button functionality
    $('#resetButton').click(function() {
        $('#criteriaForm')[0].reset();
        $('#work_center, #device_name, #test_program, #lot, #wafer, #parameter').html('');
    });

    $('#parameter-button').click(function() {
        const div1 = $('#parameter-1');
        const div2 = $('#parameter-2');
        const parameterLabel = $('label[for="parameter"]');
        const xParameterLabel = $('label[for="parameter-x"]');
        const yParameterLabel = $('label[for="parameter-y"]');

        if (div1.css('display') === 'flex') {
            div1.css('display', 'none');
            parameterLabel.css('display', 'none');
            
            div2.css('display', 'flex');
            xParameterLabel.css('display', 'block');
            yParameterLabel.css('display', 'block');
            $(this).html('&#x2BC7;');
        } else {
            div1.css('display', 'flex');
            parameterLabel.css('display', 'block');
            
            div2.css('display', 'none');
            xParameterLabel.css('display', 'none');
            yParameterLabel.css('display', 'none');
            $(this).html('&#x2BC8;');
        }

        const selectedWafer = $('#wafer').val();
        console.log(selectedWafer);
        if (selectedWafer.length > 0) {
            selectedValues.Wafer_ID = selectedWafer;
            fetchOptions(selectedValues, $('#parameter'), 'parameter');
            fetchOptions(selectedValues, $('#parameter-x'), 'parameter');
            fetchOptions(selectedValues, $('#parameter-y'), 'parameter');
        }
        
    });
    
});
</script>

<!-- Modal structure -->
<div id="confirmationModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="inline-block overflow-hidden text-left align-middle transition-all transform bg-white rounded-lg shadow-xl sm:max-w-lg sm:w-full">
        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                Your data will be displayed using 
            </h3>
            <div class="mt-2">
                <p id="confirmationText" class="text-md text-gray-1200 font-bold italic" >
                    <!-- Selection criteria will be inserted here dynamically -->
                </p>
            </div>
        </div>
        <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="button" id="confirmExecute" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                Proceed
            </button>
            <button type="button" id="cancelExecute" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Execute button to open modal
    document.getElementById('executeButton').addEventListener('click', function (event) {
        event.preventDefault(); // Prevent the form from submitting immediately

        // Initialize criteria object to collect selected criteria
        let criteria = {};
/*        
        criteria.facility = Array.from(document.getElementById('facility').selectedOptions).map(option => option.text).join(', ');
        criteria.work_center = Array.from(document.getElementById('work_center').selectedOptions).map(option => option.text).join(', ');
        criteria.device_name = Array.from(document.getElementById('device_name').selectedOptions).map(option => option.text).join(', ');
        criteria.test_program = Array.from(document.getElementById('test_program').selectedOptions).map(option => option.text).join(', ');
        criteria.lot = Array.from(document.getElementById('lot').selectedOptions).map(option => option.text).join(', ');
        criteria.wafer = Array.from(document.getElementById('wafer').selectedOptions).map(option => option.text).join(', ');
        criteria.parameters = Array.from(document.getElementById('parameter').selectedOptions).map(option => option.text).join(', ');
        criteria.X_parameters = Array.from(document.getElementById('parameter-x').selectedOptions).map(option => option.text).join(', ');
        criteria.Y_parameters = Array.from(document.getElementById('parameter-y').selectedOptions).map(option => option.text).join(', ');

        const selectedGroupX = document.querySelector('input[name="group-x[]"]:checked');
        const selectedGroupY = document.querySelector('input[name="group-y[]"]:checked');
        criteria.group_x = selectedGroupX ? selectedGroupX.nextElementSibling.textContent : 'None';
        criteria.group_y = selectedGroupY ? selectedGroupY.nextElementSibling.textContent : 'None';
*/
        const selectedChartType = document.querySelector('input[name="type"]:checked');
        const parameterXCount = Array.from(document.getElementById('parameter-x').selectedOptions).length;
        const parameterYCount = Array.from(document.getElementById('parameter-y').selectedOptions).length;
        const parameterCount = Array.from(document.getElementById('parameter').selectedOptions).length 
            + parameterXCount 
            + parameterYCount;

        if (selectedChartType) {
            criteria.chart_type = selectedChartType.nextElementSibling.textContent;
        } else {
            if (parameterCount === 1) {
                if (parameterXCount === 1 && parameterYCount === 0) {
                    criteria.chart_type = 'Cumulative Probability Graph [Default]';
                } else if (parameterYCount === 1 && parameterXCount === 0) {
                    criteria.chart_type = 'Line Chart [Default]';
                } else {
                    criteria.chart_type = 'Line Chart [Default]'; // Fallback for other cases
                }
            } else if (parameterCount >= 2) {
                criteria.chart_type = 'Scatter Plot [Default]';
            } else {
                criteria.chart_type = 'Please select a chart type after selecting the needed criteria.';
            }
        }

        // Build the criteria text without "Chart type:" prefix
        let criteriaText = '';
        for (let key in criteria) {
            // Use a conditional check to exclude "Chart type:" label from the output
            if (key === 'chart_type') {
                criteriaText += `${criteria[key]}\n`;
            } else {
                criteriaText += `${key.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}: ${criteria[key]}\n`;
            }
        }



        // Show modal with criteria
        document.getElementById('confirmationText').innerText = criteriaText;
        document.getElementById('confirmationModal').classList.remove('hidden');
    });

    // Confirm button to submit the form
    document.getElementById('confirmExecute').addEventListener('click', function () {
        document.getElementById('criteriaForm').submit();
    });

    // Cancel button to close modal without submitting
    document.getElementById('cancelExecute').addEventListener('click', function () {
        document.getElementById('confirmationModal').classList.add('hidden');
    });
});

function toggleModal() {
    const modal = document.getElementById('confirmationModal');
    modal.classList.toggle('hidden');
}
</script>
