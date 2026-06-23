<!-- BEGIN: MAIN -->
<!-- FILE: plugins/visitor_stats/tpl/visitor_stats.admin.tpl -->

<!-- Кнопка очистки -->
<div class="mb-3">
    <a href="{PHP|cot_url('admin', 'm=other&p=visitor_stats&clear=1')}" class="btn btn-danger"
	onclick="return confirm('{PHP.L.visitor_stats_clear_confirm}')">
        {PHP.L.visitor_stats_clear}
	</a>
</div>

<!-- Карточки статистики -->
<div class="row row-cols-1 row-cols-md-4 g-3 mb-4">
    <div class="col">
        <div class="card text-center">
            <div class="card-header">{PHP.L.visitor_stats_total_visits}</div>
            <div class="card-body display-6">{VAL_TOTAL}</div>
		</div>
	</div>
    <div class="col">
        <div class="card text-center">
            <div class="card-header">{PHP.L.visitor_stats_human_visits}</div>
            <div class="card-body display-6 text-success">{VAL_HUMAN}</div>
		</div>
	</div>
    <div class="col">
        <div class="card text-center">
            <div class="card-header">{PHP.L.visitor_stats_bot_visits}</div>
            <div class="card-body display-6 text-warning">{VAL_BOT}</div>
		</div>
	</div>
    <div class="col">
        <div class="card text-center">
            <div class="card-header">{PHP.L.visitor_stats_unique_visitors}</div>
            <div class="card-body display-6 text-primary">{VAL_UNIQUE}</div>
		</div>
	</div>
</div>

<!-- Фильтр -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="{PHP|cot_url('admin', 'm=other&p=visitor_stats')}" class="row g-2 align-items-center">
            <!-- Скрытые поля, чтобы не потерять p=visitor_stats -->
            <input type="hidden" name="m" value="other">
            <input type="hidden" name="p" value="visitor_stats">
            <div class="col-auto">
                <label class="form-label me-1">{PHP.L.visitor_stats_period}:</label>
                <input type="number" name="days" value="{VAL_DAYS}" min="1" max="365" class="form-control form-control-sm" style="width: 80px;">
			</div>
            <div class="col-auto">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="only_bots" value="1" 
					<!-- IF {VAL_ONLY_BOTS} -->checked<!-- ENDIF -->>
                    <label class="form-check-label">{PHP.L.visitor_stats_only_bots}</label>
				</div>
			</div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">{PHP.L.Apply}</button>
			</div>
		</form>
	</div>
</div>

<!-- Таблица журнала -->
<div class="card">
    <div class="card-header">{PHP.L.visitor_stats_log}</div>
    <div class="card-body table-responsive">
        <table class="table table-striped table-bordered table-sm align-middle">
            <thead class="table-dark">
                <tr>
                    <th>{PHP.L.visitor_stats_date}</th>
                    <th>{PHP.L.visitor_stats_ip}</th>
                    <th>{PHP.L.visitor_stats_country}</th>
                    <th>{PHP.L.visitor_stats_browser}</th>
                    <th>{PHP.L.visitor_stats_os}</th>
                    <th>{PHP.L.visitor_stats_device_type}</th>
                    <th>{PHP.L.visitor_stats_device_model}</th>
                    <th>{PHP.L.visitor_stats_isp}</th>
                    <th>{PHP.L.visitor_stats_vpn}</th>
					<th>{PHP.L.visitor_stats_blocked}</th>
                    <th>{PHP.L.visitor_stats_bot_label}</th>
                    <th>{PHP.L.visitor_stats_unique_label}</th>
                    <th>{PHP.L.visitor_stats_crawler}</th>
                    <th>{PHP.L.visitor_stats_page}</th>
                    <th>{PHP.L.visitor_stats_referer}</th>
				</tr>
			</thead>
            <tbody>
                <!-- BEGIN: LOG_ROW -->
                <tr class="{LOG_ODDEVEN}">
                    <td>{LOG_DATE}</td>
                    <td><code>{LOG_IP}</code></td>
                    <td>{LOG_COUNTRY}</td>
                    <td>{LOG_BROWSER}</td>
                    <td>{LOG_OS}</td>
                    <td>{LOG_DEVICE_TYPE}</td>
                    <td>{LOG_DEVICE_MODEL}</td>
                    <td>{LOG_ISP}</td>
                    <td>{LOG_IS_VPN}</td>
					<td>{LOG_BLOCKED}</td>
                    <td>{LOG_IS_BOT}</td>
                    <td>{LOG_UNIQUE}</td>
                    <td>{LOG_CRAWLER}</td>
                    <td><code>{LOG_PAGE}</code></td>
                    <td>{LOG_REFERER}</td>
				</tr>
                <!-- END: LOG_ROW -->
			</tbody>
		</table>
	</div>
</div>
<!-- IF {PAGINATION} -->
<nav aria-label="my pagination">
	<ul class="pagination justify-content-center mt-5">
		{PREVIOUS_PAGE}
		{PAGINATION}
		{NEXT_PAGE}
	</ul>
</nav>
<!-- ENDIF -->
<!-- END: MAIN -->
