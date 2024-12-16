<?php
/**
 * Plugin Name: eZe Fees Create Batch
 * Plugin URI: https://sdssmartoffice.com
 * Description: A plugin to create batch fees.
 * Version: 1.0
 * Author: eZe School Management
 * Author URI: https://sdssmartoffice.com
 * License: GPL2
 */

// Shortcode to display the plugin functionality
function eze_batch_fees_create_shortcode() {
    global $wpdb;

    // Variables for output
    $session = isset($_POST['session']) ? sanitize_text_field($_POST['session']) : '';
    $std_acc_id = isset($_POST['std_acc_id']) ? intval($_POST['std_acc_id']) : '';
    $session_start_date = isset($_POST['session_start_date']) ? sanitize_text_field($_POST['session_start_date']) : '';
    $session_end_date = isset($_POST['session_end_date']) ? sanitize_text_field($_POST['session_end_date']) : '';

    // Fetch the year from the session filter (e.g., 2024)
    $session_year = isset($_POST['session']) ? $_POST['session'] : date('Y'); // Default to current year if no filter

    // Set the default session start and end dates based on the year
    $session_start_date = date('01/01/Y', strtotime($session_year . '-01-01'));
    $session_end_date = date('31/12/Y', strtotime($session_year . '-12-31'));

    // Table names
    $classinfo_table = $wpdb->prefix . "eze_classinfo";
    $payment_receivable_table = $wpdb->prefix . "eze_payment_receivable";
	$fees_category_list_table = $wpdb->prefix . "eze_fees_category_list";
	
    // Variables for fetched data
    $fees_data = [];
    $payment_receivable_data = [];

    if (!empty($session) && !empty($std_acc_id)) {
        // Fetch student
			$query = "
				SELECT a.student_name, mobile_1
				FROM $admissions_table a
				WHERE a.student_id = %d
			";

			$results = $wpdb->get_row($wpdb->prepare($query, $std_acc_id));

        if ($results) {
            $student_name = $results->student_name;
			$mobile_1 = $results->mobile_1;

            // Fetch payment receivable data
            $payment_query = "
                SELECT *
                FROM $payment_receivable_table
                WHERE session = %s AND std_acc_id = %d
            ";
            $payment_receivable_data = $wpdb->get_results($wpdb->prepare($payment_query, $session, $std_acc_id));
        } else {
            $student_name = 'Student not found.';
        }
    }

    ob_start();
    ?>
    <div class="eze-fees-container">
		<!-- Left-side table -->
		<div class="eze-fees-left" style="background-color: #f4f6f7; border-radius: 8px; padding: 15px;">
			<h3 style="color: #3498db; font-size: 22px;">Fee Structure</h3>
			<div>
				<label for="session_start_date" style="font-weight: bold;">Session Start Date:</label>
				<input type="text" id="session_start_date" name="session_start_date" class="eze-session-date" value="<?php echo esc_attr($session_start_date); ?>" placeholder="DD/MM/YYYY" style="background-color: white;" />
			</div>
			<br>
			<form method="POST">
				<div class="form-group">
					<label for="fees_area" class="form-label" style="font-weight: bold;">Fee Bill Area</label>
					<input type="text" id="fees_area" class="form-input" value="School Payment" readonly style="background-color: white;" />

				</div>

				<div class="form-group">
					<label for="fees_category" class="form-label" style="font-weight: bold;">Fee Bill Category</label>
					<select id="fees_category" name="fees_category" class="form-input" style="width: 100%; background-color: white;">
						<option value="">Select Category</option>
						<?php
						global $wpdb;
						$fees_category_list_table = $wpdb->prefix . "eze_fees_category_list";
						
						// Fetch categories from the database
						$categories = $wpdb->get_results("SELECT fees_category FROM $fees_category_list_table", ARRAY_A);
						
						if (!empty($categories)) {
							foreach ($categories as $category) {
								$fees_category = esc_html($category['fees_category']);
								echo "<option value=\"$fees_category\">$fees_category</option>";
							}
						} else {
							echo '<option value="">No categories available</option>';
						}
						?>
					</select>
				</div>

				<div class="form-group">
					<label for="description" class="form-label" style="font-weight: bold;">Description</label>
					<input type="text" id="description" class="form-input" placeholder="Description" style="background-color: white;" />
				</div>

				<div class="form-group">
					<label for="payment_frequency" class="form-label" style="font-weight: bold;">Payment Frequency</label>
					<input type="text" id="payment_frequency" class="form-input" value="Yearly" readonly style="background-color: white;" />
				</div>

				<div class="form-group">
					<label for="fees_amount" class="form-label" style="font-weight: bold;">Fees Amount</label>
					<input type="number" name="fees_amount[]" id="fees_amount" class="form-input" placeholder="Enter amount" step="0.01" style="background-color: white;" />
				</div>
			</form>
		</div>

        <!-- Filter form and main table -->
        <div class="eze-fees-main">
            <div class="eze-fees-filter">
				<form method="post">
					<div style="display: flex; gap: 10px;">
						<div style="display: flex; flex-direction: column; width: 50px;">
							<label for="session">Session:</label>
							<input type="text" id="session" class="eze-session" name="session" placeholder="Enter session" value="<?php echo esc_attr($session); ?>" style="width: 100%;">
						</div>
					</div>

<div style="display: flex; gap: 10px;">
    <div style="display: flex; flex-direction: column; width: 50px;">
        <label for="version">Version:</label>
        <select id="version" class="eze-version" name="version" style="width: 100%;">
            <option value="" disabled selected style="color: lightgray;">Select Version</option>
            <?php
            // Fetch distinct versions from the 'eze_classinfo' table
            $versions = $wpdb->get_results("SELECT DISTINCT version FROM $classinfo_table WHERE version != ''", ARRAY_A);
            
            if (!empty($versions)) {
                foreach ($versions as $version) {
                    // Get the version value
                    $version_value = esc_html($version['version']);
                    // Output each version as an option in the dropdown
                    echo "<option value=\"$version_value\" " . selected($version_value, $version, false) . ">$version_value</option>";
                }
            } else {
                echo '<option value="">No versions available</option>';
            }
            ?>
        </select>
    </div>
</div>


					<div style="display: flex; gap: 10px;">
						<div style="display: flex; flex-direction: column; width: 50px;">
							<label for="class">Class:</label>
							<select id="class" class="eze-class" name="class" style="width: 100%;">
								<option value="" disabled selected style="color: lightgray;">Select Class</option>
								<?php 
								$classes = ['PLAY', 'NURSERY', 'KG', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN'];
								foreach ($classes as $class_option) {
									echo '<option value="' . $class_option . '" ' . selected($class, $class_option, false) . '>' . $class_option . '</option>';
								}
								?>
							</select>
						</div>
					</div>

					<div style="display: flex; gap: 10px;">
						<div style="display: flex; flex-direction: column; width: 50px;">
							<label for="group">Group:</label>
							<select id="group" class="eze-group" name="group" style="width: 100%;">
								<option value="" selected style="color: lightgray;">Select Group</option>
								<option value="Science" <?php selected($group, 'Science'); ?>>Science</option>
								<option value="Business Studies" <?php selected($group, 'Business Studies'); ?>>Business Studies</option>
								<option value="Humanities" <?php selected($group, 'Humanities'); ?>>Humanities</option>
							</select>
						</div>
					</div>

					<button type="button" id="generate-fees-btn" class="eze-generate-btn" style="margin-top: 10px;">Generate Fee</button>
				</form>
            </div>

            <div class="eze-fees-table">
				<h3 style="color: #e74c3c; font-size: 20px;">Fee Receivable</h3>
                <table border="1" cellspacing="0" cellpadding="5">
                    <thead>
                        <tr>
                            <th>Session</th>
                            <th>Student ID</th>
                            <th>Fee Bill Area</th>
                            <th>Fee Bill Category</th>
                            <th>Description</th>
                            <th>Subtotal</th>
                            <th>Discount</th>
                            <th>Receivable Amount</th>
                            <th>Due Date</th>
                            <th>Notes</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody id="payment-receivable-table">
                        <!-- Dynamic rows will be added here -->
                    </tbody>
                </table>
                <button type="button" id="save-data-btn" class="eze-save-btn" disabled>Save Data</button>
            </div>
        </div>
    </div>

    <style>
        .eze-session-date {
            width: 150px;
            padding: 5px;
            margin-right: 10px;
        }

        .eze-filter-btn {
            padding: 5px 10px;
            background-color: #0073aa;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        .eze-filter-btn:hover {
            background-color: #005b8c;
        }

        .eze-fees-container {
            display: flex;
            justify-content: space-between;
            gap: 20px; /* Space between the left and right columns */
        }

        .eze-fees-left {
            width: 20%; /* Left table takes 30% of the width */
        }

        .eze-fees-main {
            width: 78%; /* Right main content takes 65% of the width */
        }

        .eze-fees-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .eze-fees-table td, .eze-fees-table th {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .eze-generate-btn, .eze-save-btn {
            padding: 10px;
            background-color: #0073aa;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        .eze-generate-btn:hover, .eze-save-btn:hover {
            background-color: #005b8c;
        }

        .eze-student-info {
            margin-bottom: 20px;
        }
    </style>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Listen for changes to the fee category select
			document.getElementById('fees_category').addEventListener('change', function() {
				var selectedCategory = this.value;
				var descriptionField = document.getElementById('description');

				// Set the description field to the selected category
				if (selectedCategory) {
					descriptionField.value = selectedCategory;
				} else {
					descriptionField.value = ''; // Clear if no category is selected
				}
			});
		});
	</script>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// Initialize Flatpickr for session start and end dates
			flatpickr('#session_start_date', {
				dateFormat: 'd/m/Y', // Format as DD/MM/YYYY
			});

			flatpickr('#session_end_date', {
				dateFormat: 'd/m/Y', // Format as DD/MM/YYYY
			});

			// Add an event listener to the session input field
			document.getElementById('session').addEventListener('input', function() {
				const sessionValue = this.value; // Get the value of the session field
				const sessionDateInput = document.getElementById('session_start_date'); // Reference to the session start date field

				// Always set the session start date to the first date of the entered year
				if (sessionValue) {
					sessionDateInput.value = `01/01/${sessionValue}`;
				} else {
					sessionDateInput.value = ''; // Clear session start date if session is empty
				}
			});
		});
	</script>

	<script>
	document.addEventListener('DOMContentLoaded', function () {
		const generateFeesButton = document.getElementById('generate-fees-btn');
		const feesTableBody = document.getElementById('payment-receivable-table');

		generateFeesButton.addEventListener('click', function () {
			// Gather data from form inputs and dropdowns
			const session = document.getElementById('session').value;
			const version = document.getElementById('version').value;
			const className = document.getElementById('class').value;
			const group = document.getElementById('group').value;

			const feeBillArea = document.getElementById('fees_area').value;
			const feeBillCategory = document.getElementById('fees_category').value;
			const description = document.getElementById('description').value;
			const feesAmount = parseFloat(document.getElementById('fees_amount').value || 0).toFixed(2);
			const dueDate = document.getElementById('session_start_date').value;

			if (!session || !version || !className) {
				alert('Please fill in the required filters: Session, Version, and Class.');
				return;
			}

			// Send AJAX request to fetch student IDs
			fetch(ajaxurl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'generate_fees_table',
					session: session,
					version: version,
					class: className,
					group: group
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					// Clear existing rows
					feesTableBody.innerHTML = '';

					// Populate table with fetched student IDs
					data.data.forEach(student => {
						const row = document.createElement('tr');

						row.innerHTML = `
							<td>${session}</td>
							<td>${student.student_id}</td>
							<td>${feeBillArea}</td>
							<td>${feeBillCategory}</td>
							<td>${description}</td>
							<td>${feesAmount}</td>
							<td>0.00</td>
							<td>${feesAmount}</td>
							<td>${dueDate}</td>
							<td></td>
							<td></td>
						`;

						feesTableBody.appendChild(row);
					});

					// Enable Save Data button
					document.getElementById('save-data-btn').disabled = false;
				} else {
					alert(data.data || 'No students found.');
				}
			})
			.catch(error => {
				console.error('Error:', error);
				alert('Failed to generate fees. Please try again.');
			});
		});
	});
	</script>

	<script>
	document.addEventListener('DOMContentLoaded', function () {
		const saveDataButton = document.getElementById('save-data-btn');
		const feesTableBody = document.getElementById('payment-receivable-table');

		saveDataButton.addEventListener('click', function () {
			// Gather all rows in the table
			const rows = feesTableBody.getElementsByTagName('tr');
			
			if (rows.length === 0) {
				alert('No data to save!');
				return;
			}

			// Gather data to send in AJAX
			const rowsData = [];
			for (let row of rows) {
				const cells = row.getElementsByTagName('td');
				const rowData = {
					session: cells[0].innerText,
					student_id: cells[1].innerText,
					fee_bill_area: cells[2].innerText,
					fee_bill_category: cells[3].innerText,
					description: cells[4].innerText,
					subtotal: cells[5].innerText,
					discount: cells[6].innerText,
					receivable_amount: cells[7].innerText,
					due_date: cells[8].innerText,
					notes: cells[9].innerText,
					created_by: cells[10].innerText,
				};
				rowsData.push(rowData);
			}

			// Send AJAX request to save the data
			fetch(ajaxurl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'save_payment_data',
					data: JSON.stringify(rowsData)
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					alert('Data saved successfully!');

					// Clear the table
					feesTableBody.innerHTML = '';

					// Disable the save button again
					saveDataButton.disabled = true;
				} else {
					alert('Error saving data: ' + (data.message || 'Something went wrong.'));
				}
			})
			.catch(error => {
				console.error('Error:', error);
				alert('Failed to save data. Please try again.');
			});
		});
	});
	</script>

	<?php

    return ob_get_clean();
}

	add_shortcode('eze_batch_fees_create', 'eze_batch_fees_create_shortcode');

	add_action('wp_enqueue_scripts', function () {
		wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', [], null, true);
		wp_enqueue_style('flatpickr-style', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
	});

	add_action('wp_ajax_generate_fees_table', 'generate_fees_table');
	add_action('wp_ajax_nopriv_generate_fees_table', 'generate_fees_table');

	function generate_fees_table() {
		global $wpdb;

		// Fetch posted data
		$session = sanitize_text_field($_POST['session']);
		$version = sanitize_text_field($_POST['version']);
		$class = sanitize_text_field($_POST['class']);
		$group = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : '';

		// Table name
		$classinfo_table = $wpdb->prefix . "eze_classinfo";

		// Build query based on filters
		$query = "SELECT student_id FROM $classinfo_table WHERE session = %s AND version = %s AND class = %s";
		$query_params = [$session, $version, $class];

		if (!empty($group)) {
			$query .= " AND `group` = %s"; // Enclose 'group' in backticks
			$query_params[] = $group;
		}

		$students = $wpdb->get_results($wpdb->prepare($query, ...$query_params), ARRAY_A);

		// Return student IDs as JSON
		if ($students) {
			wp_send_json_success($students);
		} else {
			wp_send_json_error('No students found.');
		}

		wp_die();
	}

	add_action('wp_ajax_save_payment_data', 'save_payment_data');
	add_action('wp_ajax_nopriv_save_payment_data', 'save_payment_data');

	function save_payment_data() {
		global $wpdb;

		// Get the posted data (JSON string)
		$data = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : [];

		if (empty($data)) {
			wp_send_json_error(['message' => 'No data provided']);
		}

		$payment_receivable_table = $wpdb->prefix . 'eze_payment_receivable';

		foreach ($data as $row) {
			// Check for duplicates
			$existing_row = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM $payment_receivable_table WHERE session = %s AND std_acc_id = %d AND fee_bill_category = %s AND description = %s",
				$row['session'], $row['student_id'], $row['fee_bill_category'], $row['description']
			));

			if ($existing_row > 0) {
				continue; // Skip inserting if the row already exists
			}

			// Insert the data
			$wpdb->insert(
				$payment_receivable_table,
				[
					'session' => $row['session'],
					'std_acc_id' => $row['student_id'],
					'fee_bill_area' => $row['fee_bill_area'],
					'fee_bill_category' => $row['fee_bill_category'],
					'description' => $row['description'],
					'subtotal' => $row['subtotal'],
					'discount' => $row['discount'],
					'receivable_amount' => $row['receivable_amount'],
					'due_date' => date('Y-m-d', strtotime($row['due_date'])), // Convert date to Y-m-d format
					'notes' => $row['notes'],
					'created_by' => $row['created_by']
				]
			);
		}

		wp_send_json_success();
	}
