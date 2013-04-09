<table>
    <tbody>
        <tr>
            <td class="left"><a href="/" class="button">&lt;- Back</a></td>
            <td class="right"><h1>Create a new <b>App</b></h1></td>
        </tr>
    </tbody>
</table>
<form action="/apps/new" method="POST">
    <div class="well">
        <table>
            <tbody>
                <tr>
                    <td>Name</td>
                    <td>=></td>
                    <td>
                        <input type="text" name="appname" placeholder="name" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="right"><input type="submit" class="button large" value="Create !"></div>
</form>