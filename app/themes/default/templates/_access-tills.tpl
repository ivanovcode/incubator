<form class="animated flash">
    <input class="u-full-width" type="text" placeholder="ID сотрудника" autocomplete="off" id="StaffId" maxlength="10">
    <input class="u-full-width" type="text" placeholder="ФИО" autocomplete="off" id="StaffName" disabled="disabled">
    <input class="u-full-width" type="text" placeholder="ID кассы" autocomplete="off" id="CassaId" maxlength="10">
    <input class="u-full-width" type="text" placeholder="Название счета" autocomplete="off" id="CassaTitle" disabled="disabled">
    <input class="button-primary" type="submit" value="Создать права" disabled="disabled">
    <input class="button hidden" type="button" value="Отменить">
</form>
<div class="table-responsive">
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
            <td data-key="StaffId">{*list:id*}</td>
            <td>{*list:name*}</td>
            <td data-key="CassaId">{*list:login*}</td>
            <td>{*list:password*}</td>
            <td><span data-mode="delete">[удалить]</span></td>
        </tr>
        {%}
        </tbody>
    </table>
</div>
<script>
    var tills = JSON.parse('{*tills*}');
    var staffs = JSON.parse('{*staffs*}');
</script>