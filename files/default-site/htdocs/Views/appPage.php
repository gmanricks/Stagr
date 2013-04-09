{% if app %}
	<script>
		function addRow () {
			var row = document.createElement("TR");

			var name_col = document.createElement("TD");
			var name_input = document.createElement("INPUT");
			name_input.setAttribute("type", "text");
			name_input.setAttribute("name", "envname[]");
			name_input.setAttribute("placeholder", "Name");
			name_col.appendChild(name_input);

			var middle_col = document.createElement("TD");
			middle_col.appendChild(document.createTextNode("=>"));

			var value_col = document.createElement("TD");
			var value_input = document.createElement("INPUT");
			value_input.setAttribute("type", "text");
			value_input.setAttribute("name", "envvalue[]");
			value_input.setAttribute("placeholder", "Value");
			value_col.appendChild(value_input);


			row.appendChild(name_col);
			row.appendChild(middle_col);
			row.appendChild(value_col);

			document.getElementById("envvar_table").appendChild(row);
		} 
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
	<form action="/apps/{{ app.name }}/settings/save" method="post">
	<div class="well">
		<table>
			<tbody>
				<tr>
					<td>Document Root</td>
					<td>=></td>
					<td>
						<input type="text" name="docroot" placeholder="/" value="{{ attribute(app.settings, 'doc-root') }}" />
					</td>
				</tr>
				<tr>
					<td>PHP Timezone</td>
					<td>=></td>
					<td>
						<select name="timezone">
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
						<select name="exectime">
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
						<select name="memlimit">
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
						<select name="apclimit">
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
						<select name="uploadsize">
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
						<select name="postsize">
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
						<select name="outputsize">
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
						<input type="radio" {% if app.settings.php.short_open_tag == "On" %} checked="checked" {% endif %} name="shorttags" value="On" /> ON  <--> 
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
							<td class="right"><a onClick="addRow()" class="button green">Add Var</a></td>
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
				<tr>
					<td><input type="text" name="envname[]" placeholder="Name" /></td>
					<td>=></td>
					<td><input type="text" name="envvalue[]" placeholder="Value" /></td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="rightbar">
		<input type="submit" class="button large" value="Save" />
	</div>
	</form>
{% else %}
	<a href="/" class="button">&lt;- Back</a>
	<p>App Not Found</p>
{% endif %}
