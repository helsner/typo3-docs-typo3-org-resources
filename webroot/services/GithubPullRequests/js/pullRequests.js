$(document).ready(function() {
	var ongoingCalls = 0;
	var issues = [];
	var projects = [];

	$.ajax({
		// Get all projects from the TYPO3-Documentation user
		url: 'https://api.github.com/users/TYPO3-Documentation/repos',
		success: function(data, status, xhr) {
			// Prepare values for the loading indicator (set at 10% by default)
			var progress = 10;
			var progressFraction = 90 / data.length;
			var progressBar = $('#datatables_loader_progress');
			// Loop on projects
			for (var i = 0; i < data.length; i++) {
				var projectName = data[i].name;
				// Increase the number of AJAX calls launched, to keep track of status
				ongoingCalls++;
				$.ajax({
					// Get list of pull requests for each project
					url: 'https://api.github.com/repos/TYPO3-Documentation/' + projectName + '/pulls?state=all',
					success: function(data, status, xhr) {
						// On return, decrease number of pending calls
						ongoingCalls--;
						// Increase progress bar
						progress += progressFraction;
						if (progress > 100) {
							progress = 100;
						}
						progressBar.attr('style', 'width: ' + progress + '%');
						progressBar.html(Math.round(progress) + '%');
						// Store pull requests in global array
						var currentProject = '';
						for (var j = 0; j < data.length; j++) {
							var newIssue = {};
							newIssue.url = data[j].html_url;
							newIssue.topic = data[j].title;
							currentProject = data[j].base.repo.name;
							newIssue.project = currentProject;
							newIssue.status = data[j].state;
							newIssue.created = data[j].created_at;
							newIssue.user = data[j].user.login;
							newIssue.userUrl = data[j].user.html_url;
							issues.push(newIssue);
						}
						// If there was at least one result, keep the name of the project
						// NOTE: this should have been done at a later point with the DataTable API,
						// but I couldn't get the examples to work
						if (currentProject != '') {
							projects.push(currentProject);
						}
						// If the last call returned, we have all the data
						// Initialize DataTables
						if (ongoingCalls == 0) {
							$('#pull_requests').DataTable({
								data: issues,
								responsive: true,
								// Map data to columns
								columns: [
									{
										targets: 'column-status',
										data: 'status',
										// Render status as Bootstrap label
										render: function(data, type, row, meta) {
											var labelClass;
											if (data == 'open') {
												labelClass = 'label-danger';
											} else {
												labelClass = 'label-success';
											}
											return '<span class="label ' + labelClass + '">' + data + '</span>';
										}
									},
									{
										targets: 'column-date',
										data: 'created',
										// Parse and format date for output
										render: function(data, type, row, meta) {
											var date = moment(data);
											return date.format('YYYY-MM-DD HH:mm');
										}
									},
									{
										targets: 'column-topic',
										data: 'topic',
										// For display, add link to pull request
										render: function(data, type, row, meta) {
											if (type == 'display') {
												return '<a href="' + row.url + '">' + data + '</a>';
											} else {
												return data;
											}
										}
									},
									{
										targets: 'column-project',
										data: 'project'
									},
									{
										targets: 'column-author',
										data: 'user',
										// For display, add link to user profile
										render: function(data, type, row, meta) {
											if (type == 'display') {
												return '<a href="' + row.userUrl + '">' + data + '</a>';
											} else {
												return data;
											}
										}
									}
								],
								// Default search, on "open" status
								searchCols: [
									{search: 'open'},
									null,
									null,
									null,
									null
								],
								// Default ordering: date column, descending
								order: [1, 'desc'],
								initComplete: function () {
									// Initialization is done, hide progress bar and show table
									$('#datatables_loader').addClass('hidden');
									$('#datatables').removeClass('hidden');

									var dataTables = $('#pull_requests').DataTable();

									// Fill the project filter with data from the table
									var projectFilter = $('#filter_project');
									projects.sort();
									for (var i = 0; i < projects.length; i++) {
										projectFilter.append('<option value="' + projects[i] + '">' + projects[i] + '</option>');
									}

									// React to changes in the status filter
									$('#filter_status').on('change', function() {
										dataTables.columns('.column-status').search($(this).val()).draw();
									});

									// React to changes in the project filter
									projectFilter.on('change', function() {
										dataTables.columns('.column-project').search($(this).val()).draw();
									});
								}
							});
						}
					}
				});
			}
		}
	});
});