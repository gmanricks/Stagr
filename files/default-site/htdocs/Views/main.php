				<table>
					<tbody>
						<tr>
							<td class="left"><h1>Apps</h1></td>
							<td class="right"><a href="#" class="button green">New App</a></td>
						</tr>
					</tbody>
				</table>			
				{% if apps %}
					<table class="app_table">
						<tbody>
				    		{% for app in apps %}
								<tr>
									<td>{{app}}</td>
									<td class="button_column">
										<a href="apps/{{app}}/settings" class="button">Settings</a>
									</td>
								</tr>
							{% endfor %}
						</tbody>
					</table>
				{% else %}
    				No Apps currently installed.
				{% endif %}
