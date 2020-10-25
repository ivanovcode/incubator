<div class="table-responsive">
    <div id="no-more-tables">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">ID кассы</th>
                <th scope="col">Название кассы</th>
            </tr>
            </thead>
            <tbody>
            {%*till*}
            <tr>
                <td data-title="ID кассы:">{*till:id*}</td>
                <td data-title="Название кассы:">{*till:title*}</td>
            </tr>
            {%}
            </tbody>
        </table>
    </div>
</div>