<form class="animated flash">
    <select class="u-full-width" id="StaffId">
        <option></option>
        {%*staff*}
        <option value="{*staff:id*}">{*staff:name*} - #{*staff:id*}</option>
        {%}
    </select>
    <select class="u-full-width" id="CassaId">
        <option></option>
        {%*till*}
        <option value="{*till:id*}">{*till:title*} - #{*till:id*}</option>
        {%}
    </select>
    <input class="button-primary" type="submit" value="Создать права" disabled="disabled">
</form>
<div class="table-responsive">
    <div id="no-more-tables">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">ID Сотрудника</th>
                <th scope="col">ФИО</th>
                <th scope="col">ID Счета</th>
                <th scope="col">Название счета</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            {%*list*}
            <tr>
                <td data-title="ID Сотрудника:" data-key="StaffId">{*list:id*}</td>
                <td data-title="ФИО:">{*list:name*}</td>
                <td data-title="ID Счета:" data-key="CassaId">{*list:login*}</td>
                <td data-title="Название счета:">{*list:password*}</td>
                <td>
                    <span data-mode="delete">[удалить]</span>
                </td>
            </tr>
            {%}
            </tbody>
        </table>
    </div>
</div>
<script>
    var tills = JSON.parse('{*tills*}');
    var staffs = JSON.parse('{*staffs*}');
</script>