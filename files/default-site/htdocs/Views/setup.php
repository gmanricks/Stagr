<h1>Setup</h1>
<p>This seems to be the first time you are accessing this server, to get started please provide the following info:</p>
<form action="/setup/save" method="post">
    <div class="well">
        <table>
            <tbody>
                <tr>
                    <td>Email</td>
                    <td>=></td>
                    <td>
                        <input type="text" name="email" placeholder="Email Address" />
                    </td>
                </tr>
                <tr>
                    <td>SSH Public Key</td>
                    <td>=></td>
                    <td>
                        <textarea name="pubkey"></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="right">
        <input type="submit" value="Setup !" class="button large" />
    </div>
</form>