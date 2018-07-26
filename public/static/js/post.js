$.ajaxSetup({
    beforeSend: function(xhr, settings) {
	if (settings.type === 'POST' && settings.data.length) {
            // Send the token to same-origin, relative URLs only.
            // Send the token only if the method warrants CSRF protection
            // Using the CSRFToken value acquired earlier
            xhr.setRequestHeader(YodaPortal.csrf.tokenName, YodaPortal.csrf.tokenValue);
        }
    }
});
