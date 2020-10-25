<form class="animated flash" autocomplete="off">
    <select class="u-full-width" id="StaffId">
        <option></option>
        {%*staff*}
        <option value="{*staff:id*}">{*staff:name*} - #{*staff:id*}</option>
        {%}
    </select>
    <input class="u-full-width" type="text" placeholder="Введите номер телефона" autocomplete="off" id="Phone">
    <select class="u-full-width" id="Role">
        <option value="0" selected="selected">Сотрудник</option>
        <option value="1">Администратор</option>
    </select>
    <label class="pure-material-checkbox u-full-width auth-check hidden">
        <input id="Auth" type="checkbox">
            <span data-text="Доступ в Админку">Доступ в Админку</span>
    </label>
    <div class="row auth-form hidden">
        <div class="six columns">
            <input class="u-full-width" type="text" placeholder="Введите Логин" autocomplete="off" id="Login">
        </div>
        <div class="six columns">
            <input class="u-full-width" type="password" placeholder="Введите Пароль" autocomplete="off" id="Password">
        </div>
    </div>
    <input class="button-primary" data-mode="create" type="submit" value="Создать права" disabled="disabled">
    <input class="button hidden" type="button" value="Отменить">
</form>
<div class="table-responsive">
    <div id="no-more-tables">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">ID Сотрудника</th>
                <th scope="col" class="hidden">Логин</th>
                <th scope="col">ФИО</th>
                <th scope="col">Мобильный номер</th>
                <th scope="col">Роль</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            {%*list*}
            <tr>
                <td data-title="ID Сотрудника" data-key="StaffId">{*list:id*}</td>
                <td data-title="Логин" data-key="Login" class="hidden">{*list:login*}</td>
                <td data-title="ФИО">{*list:name*}</td>
                <td data-title="Мобильный номер:" data-key="Phone">{*list:phone*}</td>
                <td data-title="Роль:" data-key="Role" data-value="{*list:role*}">{?*list:role*}Администратор{?* list:root *} <sup>root</sup>{?}{?!}Сотрудник{?} {?* list:role = 1 & list:auth = 1 *} <br><span style="font-size:12px;"><i class="far fa-user"></i>&nbsp;&nbsp;{*list:login*}</span>{?}</td>
                <td>
                    <span data-mode="edit">[редактировать]</span>
                    <span data-mode="delete" style="color:red">[удалить]</span>
                </td>
            </tr>
            {%}
            </tbody>
        </table>
    </div>
</div>
<script>
    var staffs = JSON.parse('{*staffs*}');
</script>