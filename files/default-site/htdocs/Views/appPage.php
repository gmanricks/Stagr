{% if app %}
	<script>
	$(function() {

		function validate(docRoot) {
			if (docRoot.indexOf("..") !== -1) {
				alert("Invalid Doc Root");
				return false;
			}
			return true;
		}

		$('#add_var').click(function() {
			var row = $('<tr class="envrow" />');
			$('<td>').appendTo(row).append($('<input type="text" name="envname" placeholder="Name">'));
			$('<td>').appendTo(row).text('=>');
			$('<td>').appendTo(row).append($('<input type="text" name="envvalue" placeholder="Value">'));
			row.appendTo('#envvar_table');
			return false;
		});

		$('#saveButton').click(function() {
			var docRoot = $.trim($('#docroot').val());
			if (validate(docRoot)) {

				// build data
				var data = {docroot: docRoot};
				$(['timezone', 'exectime', 'memlimit', 'apclimit', 'uploadsize', 'postsize', 'outputsize']).each(function() {
					data[this] = $('[name='+ this+ ']').val();
				});
				data.shorttags = $('[name=shorttag]:checked').length ? 'On' : 'Off';
				data.envs = [];
				$('.envrow').each(function() {
					var key = $.trim($(this).find('[name=envname]').val());
					var val = $.trim($(this).find('[name=envvalue]').val());
					if (key && val) {
						data.envs.push(key+ '='+ val);
					}
				});

				// send post, init ping
				$(this).text('Saving ...');
				$('#content input, #content select, #content .button').attr('disabled', true).addClass('disabled');
				$.post('/apps/{{ app.name }}/settings/save', data);
				initPing(document.location.pathname+ '?'+ (new Date()).getTime());
			}
			return false;
		}).attr('disabled', false);
	});
	</script>
	<table>
		<tbody>
			<tr>
				<td class="left"><a href="/" class="button">&lt;- Back</a></td>
				<td class="right"><h1>Settings for <b>{{ app.name }}</b></h1></td>
			</tr>
		</tbody>
	</table>
	{% if flash.info %}
		<div class="flash">{{ flash.info }}</div>
	{% endif %}
	<p class="settings_header">Settings</p>
	<div class="well">
		<table>
			<tbody>
				<tr>
					<td>Document Root</td>
					<td>=></td>
					<td>
						<input type="text" id="docroot" placeholder="/" value="{{ attribute(app.settings, 'doc-root') }}" />
					</td>
				</tr>
				<tr>
					<td>PHP Timezone</td>
					<td>=></td>
					<td>
						<select id="timezone" name="timezone">
							{% for zone in timezones %}
								<option value="{{ zone }}" {% if zone == attribute(app.settings.php, "date-timezone") %} selected="selected" {% endif %}>{{ zone }}</option>
							{% endfor %}
						</select>
					</td>
				</tr>
				<tr>
					<td>Max Execution Time</td>
					<td>=></td>
					<td>
						<select id="exectime" name="exectime">
							{% for exect in exectimes %}
								<option value="{{ exect }}" {% if exect == attribute(app.settings.php, "max_execution_time") %} selected="selected" {% endif %}>{{ exect }}</option>
							{% endfor %}
						</select>
					</td>
				</tr>
				<tr>
					<td>Memory Limit</td>
					<td>=></td>
					<td>
						<select id="memlimit" name="memlimit">
							{% for memsize in memorysizes %}
								<option value="{{ memsize }}" {% if memsize == attribute(app.settings.php, "memory_limit") %} selected="selected" {% endif %}>{{ memsize }}</option>
							{% endfor %}
						</select>
					</td>
				</tr>
				<tr>
					<td>APC Size</td>
					<td>=></td>
					<td>
						<select id="apclimit" name="apclimit">
							{% for apcsize in apcsizes %}
								<option value="{{ apcsize }}" {% if apcsize == attribute(app.settings.php, "apc-shm_size") %} selected="selected" {% endif %}>{{ apcsize }}</option>
							{% endfor %}
						</select>
					</td>
				</tr>
				<tr>
					<td>Max Upload Size</td>
					<td>=></td>
					<td>
						<select id="uploadsize" name="uploadsize">
							{% for upsize in uploadsizes %}
								<option value="{{ upsize }}" {% if upsize == attribute(app.settings.php, "upload_max_filesize") %} selected="selected" {% endif %}>{{ upsize }}</option>
							{% endfor %}
						</select>
					</td>
				</tr>
				<tr>
					<td>Max Post Size</td>
					<td>=></td>
					<td>
						<select id="postsize" name="postsize">
							{% for postsize in postsizes %}
								<option value="{{ postsize }}" {% if postsize == attribute(app.settings.php, "post_max_size") %} selected="selected" {% endif %}>{{ postsize }}</option>
							{% endfor %}
						</select>
					</td>
				</tr>
				<tr>
					<td>Output Buffer</td>
					<td>=></td>
					<td>
						<select id="outputsize" name="outputsize">
							{% for buffersize in buffersizes %}
								<option value="{{ buffersize }}" {% if buffersize == attribute(app.settings.php, "output_buffering") %} selected="selected" {% endif %}>{{ buffersize }}</option>
							{% endfor %}
						</select>
					</td>
				</tr>
				<tr>
					<td>Short Open Tags</td>
					<td>=></td>
					<td>
						<input type="radio" {% if app.settings.php.short_open_tag == "On" %} checked="checked" {% endif %} name="shorttags" value="On" id="shorttags" /> ON  &lt;--&gt;
						<input type="radio" {% if app.settings.php.short_open_tag == "Off" %} checked="checked" {% endif %}  name="shorttags" value="Off" /> OFF
					</td>
				</tr>
			</tbody>
		</table>
	</div>
				<table>
					<tbody>
						<tr>
							<td class="left"><p class="settings_header">Env Vars</p></td>
							<td class="right"><a id="add_var" class="button green">Add Var</a></td>
						</tr>
					</tbody>
				</table>
	<div class="well">
		<table>
			<tbody id="envvar_table">
				<tr>
					<td>APP_NAME</td>
					<td>=></td>
					<td>{{ app.name }}</td>
					<td>&nbsp;</td>
				</tr>
				{% for envName, envValue in app.settings.env %}
				<tr class="envrow">
					<td><input type="text" name="envname" placeholder="Name" value="{{ envName | e }}" /></td>
					<td>=></td>
					<td><input type="text" name="envvalue" placeholder="Value" value="{{ envValue | e }}" /></td>
				</tr>
				{% endfor %}
				<tr class="envrow">
					<td><input type="text" name="envname" placeholder="Name" /></td>
					<td>=></td>
					<td><input type="text" name="envvalue" placeholder="Value" /></td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="rightbar">
		<button id="saveButton" type="submit" class="button large">Save</button>
	</div>
{% else %}
	<a href="/" class="button">&lt;- Back</a>
	<p>App Not Found</p>
{% endif %}
