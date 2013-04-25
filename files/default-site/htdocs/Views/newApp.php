<table>
    <tbody>
        <tr>
            <td class="left"><a href="/" class="button">&lt;- Back</a></td>
            <td class="right"><h1>Create a new <b>App</b></h1></td>
        </tr>
    </tbody>
</table>

    <div class="well">
        <table>
            <tbody>
                <tr>
                    <td>Name</td>
                    <td>=></td>
                    <td>
                        <input id="appname" type="text" name="appname" placeholder="name" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="right"><button id="createButton" class="button large">Create !</button></div>


<script>
$(function() {
    function validate(appName) {
        console.debug("B", appName, appName.length);
        if (appName.length < 4 || appName.length > 16) {
            alert("The Apps name has to be 4-16 characters");
            return false;
        }

        var r = /^[a-z0-9]+(?:[a-z0-9]+\-)*[a-z0-9]+$/;

        if (!r.test(appName)) {
            alert("The app's name must follow the formate of a-z0-9 with optional dashes in the middle");
            return false;
        }
        return true;
    }

    $('#createButton').click(function() {
        var appName = $("#appname").val();
        console.debug("A", appName);
        if (validate(appName)) {
            $(this).text('Creating ...');
            $('input, button').attr('disabled', true).addClass('disabled');
            $.post('/apps/new', {appname: appName});
            initPing('/');
        }
        return false;
    });
});
</script>