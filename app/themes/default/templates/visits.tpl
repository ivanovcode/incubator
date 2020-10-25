<link href="app/themes/default/assets/css/{*page*}.css" rel="stylesheet" /
<div class="table-responsive">
    <div id="no-more-tables">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Последнее посещение</th>
                <th scope="col">Визиты</th>
                <th scope="col">Источник</th>
                <th scope="col">Поток</th>
                <th scope="col">Пробив</th>
                <th scope="col">ID</th>
                <th scope="col">Фото</th>
                <th scope="col">ФИО</th>
            </tr>
            </thead>
            <tbody>
            {%*staff*}
            <tr>
                <td data-title="Последнее посещение:" nowrap>{*staff:last*}</td>
                <td data-title="Визиты:">{*staff:hits*}</td>
                <td data-title="Источник:">{?* staff:ref *}<a href="{*staff:ref*}" target="_blank">{*staff:ref*}</a>{?!}прямой заход{?}</td>
                <td data-title="Поток:" nowrap><span class="badge badge-default">{*staff:domain*}</span>/ <span>{*staff:short*}</span></td>
                <td data-title="Пробив:"><span class="badge badge-{?* staff:deep > 1 *}success{?!}danger{?}">{*staff:deep*}</span></td>
                <td data-title="ID:"><a href="https://vk.com/id{*staff:friend_id*}" target="_blank">{*staff:friend_id*}</a></td>
                <td data-title="Фото:"><img class="face" src="{*staff:friend_photo*}" /></td>
                <td data-title="ФИО:">{*staff:friend_name*}</td>
            </tr>
            {%}
            </tbody>
        </table>
    </div>
</div>