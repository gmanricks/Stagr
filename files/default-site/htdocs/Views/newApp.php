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
    <div class="right"><button id="createButton" onClick="createApp()" class="button large">Create !</button></div>


<script>
    function ajax(url, cb, data) {
        var ajx = new XMLHttpRequest();

        ajx.onreadystatechange = function () {
            if (ajx.readyState == 4) { cb(ajx.status); }
        }

        if (typeof data !== "undefined") {
            ajx.open("POST", url, true);
            ajx.setRequestHeader("Content-type","application/x-www-form-urlencoded");
            ajx.send(data);
        } else {
            ajx.open("GET", url, true);
            ajx.send();
        }
    }

    function loopCb(status) {
        if (status == 200) {
            window.location = "/";
        } else {
            setTimeout(ping, 300);
        }
    }

    function ping() {
        ajax("/ping", loopCb);
    }

    function validate(appName) {
        if (appName.length > 4 && appName.length < 16) {
            alert("The Apps name has to be 5-15 characters");
            return false;
        }

        var r = /^[a-z0-9]+(?:[a-z0-9]+\-)*[a-z0-9]+$/;

        if (!r.test(appName)) {
            alert("The app's name must follow the formate of a-z0-9 with optional dashes in the middle");
            return false;
        }
        return true;
    }

    function createApp() {
        var appName = document.getElementById("appname").value;
        if (validate(appName)) {
            var b = document.getElementById("createButton");
            b.setAttribute("onClick", "");
            b.innerText = "Creating ...";

            ajax("/apps/new", ping, "appname=" + appName);   
        }
    }
</script>