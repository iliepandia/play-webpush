<!doctype html>
<html lang="en-US">
<head>
    <title>Playing with WebPush</title>
    <link href="css/style.css" rel="stylesheet" />

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
</head>
<body>
    <div id="push-config">
        <div id="push-messages">
            Checking if you can use notifications...
        </div>
        <div id="push-notifications-ui">
            Here is the UI for Push
            <button class="on">Turn On</button>
            <button class="off">Turn Off</button>
        </div>
    </div>
    <script>
        function isWebPushSupported(){
            if (!('serviceWorker' in navigator)) {
                window.console.error("ServiceWorkers Not supporter here");
                return false;
            }
            if(!('PushManager' in window)){
                window.console.error("PushManager Not supporter here");
                return false;
            }
            return true;
        }

        jQuery(document).ready(function($){

            var isPushEnabled = false;

            function sendSubscriptionToServer( subscription ){
                //TODO: Add code here that will do an AJAX call to the server to save the sub
                console.debug( "Need to save/sync this with the server" + JSON.stringify( subscription ) );
            }

            function removeSubscriptionFromServer( subscription ){
                //TODO: Add code here that will do an AJAX call to the server to remove he sub
                console.debug( "Need to remove this from the server" + JSON.stringify( subscription ) );
            }


            function unsubscribe(){
                //hide the button to avoid double pushing...
                $('#push-notifications-ui .off').hide();

                //Now I need to get service registration

                //IMPORTANT: Here I used to have this "navigator.serviceWorker.ready" but that promise NEVER resolves.
                //There is a suggestion to fix here: https://stackoverflow.com/questions/29874068/navigator-serviceworker-is-never-ready
                //but it does not make sense to me to move my script into the root folder of the web server.
                //So I order to get the registration I will just register the service again...
                navigator.serviceWorker.register('./js/service-worker.js')
                    .then(function( registration ){

                        var $msg = $('#push-messages');

                        registration.pushManager.getSubscription()
                            .then(function(subscription){

                                if(!subscription){
                                    //We don't actually have subscription... so nothing to do...
                                    //TODO: eventually update the UI?!
                                    $('#push-notifications-ui .on').show();
                                    $('#push-notifications-ui .off').hide();
                                    return;
                                }

                                //Saving it to the server...
                                removeSubscriptionFromServer(subscription);

                                //Now that we have subscription is time to call unsubscribe on it:
                                subscription.unsubscribe()
                                    .then(function(){
                                        $('#push-notifications-ui .on').show();
                                        $('#push-notifications-ui .off').hide();
                                        console.debug("Unsubscribed");
                                    })
                                    .catch(function(err){
                                        console.debug( "Unsubscription:", err );
                                        $msg.text('There is a technical error preventing us to setup the notifications. Please notify the website administrator. Code: 6');
                                        $msg.show();
                                    });

                            })
                            .catch(function(err){
                                console.debug( "Error while handing the unsubscription:", err );
                                $msg.text('There is a technical error preventing us to setup the notifications. Please notify the website administrator. Code: 5');
                                $msg.show();
                            });
                    })
                    .catch(function(err){
                        console.error("This should not happen asn I have already a working registration:", err);
                    });
            }

            function subscribe(){
                //hide the button to avoid double pushing...
                $('#push-notifications-ui .on').hide();

                //Now I need to get service registration

                //IMPORTANT: Here I used to have this "navigator.serviceWorker.ready" but that promise NEVER resolves.
                //There is a suggestion to fix here: https://stackoverflow.com/questions/29874068/navigator-serviceworker-is-never-ready
                //but it does not make sense to me to move my script into the root folder of the web server.
                //So I order to get the registration I will just register the service again...
                navigator.serviceWorker.register('./js/service-worker.js')
                    .then(function( registration ){

                        var $msg = $('#push-messages');

                        registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(
                                'BOvcaHQSDbVaYUd8VlfRXqul710kSo6BcUurtTgy0Q_yqSVFMaS8p64Xl-ee6ojZVivSXyab-wBCbqrmd6qsXmo'
                            )
                        })
                        .then(function(subscription){
                            //Subscription is a success...

                            //Updating the UI
                            $('#push-notifications-ui .on').hide();
                            $('#push-notifications-ui .off').show();

                            //Saving it to the server...
                            sendSubscriptionToServer(subscription);

                            console.debug("Subscribed");
                        })
                        .catch(function(err){
                            console.debug( "Failed to subscribe:", err );
                            if( Notification.permission === 'denied'){
                                $msg.text('Notifications have been blocked in this browser. You can allow them from your browser settings. (2)');
                                $msg.show();
                                //I need to hide the UI for configuring push as there is of no use in this case
                                $('#push-notifications-ui').hide();
                            }else{
                                $msg.text('There is a technical error preventing us to setup the notifications. Please notify the website administrator. Code: 4');
                                $msg.show();
                            }
                        });
                    })
                    .catch(function(err){
                        console.error("This should not happen asn I have already a working registration:", err);
                    });
            }

            $('#push-notifications-ui .on').click(function () {
                subscribe();
            });

            $('#push-notifications-ui .off').click(function () {
                unsubscribe();
            });

            function initializeState( registration ){
                // Are Notifications supported in the service worker?
                if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
                    $('#push-messages').text('There is a technical error preventing us to setup the notifications. Please notify the website administrator. Code: 2');
                    return;
                }

                // Check the current Notification permission.
                // If its denied, it's a permanent block until the
                // user changes the permission
                if (Notification.permission === 'denied') {
                    $('#push-messages').text('Notifications have been blocked in this browser. You can allow them from your browser settings.');
                    return;
                }

                //do I have a valid subscription yet?
                registration.pushManager.getSubscription()
                    .then(function(subscription){
                        //At this point we know that we can use Notifications so it makes sense to show the interface

                        //hide the space for error display
                        $('#push-messages').hide();

                        //show the UI
                        $('#push-notifications-ui').show();

                        if(!subscription){
                            $('#push-notifications-ui .on').show();
                            $('#push-notifications-ui .off').hide();
                            return;
                        }

                        isPushEnabled = true;
                        $('#push-notifications-ui .on').hide();
                        $('#push-notifications-ui .off').show();
                    })
                    .catch(function(err){
                        //For some reason getSubscription has FAILED... dang!
                        $('#push-messages').text('There is a technical error preventing us to setup the notifications. Please notify the website administrator. Code: 3');
                        return;
                    });

            }


            if(!isWebPushSupported()){
                //TODO: Maybe just hide the interface?
                $('#push-messages').text('You cannot activate notifications in this browser because they are not supported. You could try another browser.');
                //this is a game stopper. No need to go further
                return;
            }

            navigator.serviceWorker.register('./js/service-worker.js')
                .then(function( registration ){
                    initializeState( registration );
                })
                .catch(function(error){
                    console.error('Unable to register the service worker.', error );
                    $('#push-messages').text('There is a technical error preventing us to setup the notifications. Please notify the website administrator. Code: 1')
                })
            ;
        });
    </script>
    <script>
        function registerServiceWorker(){
            return navigator.serviceWorker.register('./js/service-worker.js')
                .then(function (registration) {
                    console.debug('Service Worker registered succesfully');
                    return registration;
                })
                .catch(function(err){
                    console.error('Unable to register the service worker', err )
                })
        }

        function askForPermission(){
            return new Promise( function (resolve, reject){
                const permissionResult = Notification.requestPermission(function(result){
                    resolve(result);
                });

                if(permissionResult){
                    permissionResult.then(resolve,reject);
                }
            })
                .then(function(permissionResult){
                    if(permissionResult !== 'granted'){
                        throw new Error("We were not granted the permission");
                    }
                })
                ;
        }

        /**
         * urlBase64ToUint8Array
         *
         * @param {string} base64String a public vavid key
         */
        function urlBase64ToUint8Array(base64String) {
            var padding = '='.repeat((4 - base64String.length % 4) % 4);
            var base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');

            var rawData = window.atob(base64);
            var outputArray = new Uint8Array(rawData.length);

            for (var i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        function subscribeUserToPush() {
            return navigator.serviceWorker.register('./js/service-worker.js')
                .then(function(registration) {
                    const subscribeOptions = {
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(
                            'BOvcaHQSDbVaYUd8VlfRXqul710kSo6BcUurtTgy0Q_yqSVFMaS8p64Xl-ee6ojZVivSXyab-wBCbqrmd6qsXmo'
                        )
                    };

                    registration.pushManager.getSubscription()
                        .then(function(pushSubscription){
                            console.log("Already subbed!?");
                            console.log(pushSubscription);
                        });

                    return registration.pushManager.subscribe(subscribeOptions);
                })
                .then(function(pushSubscription) {
                    console.log('Received PushSubscription: ', JSON.stringify(pushSubscription));
                    console.log(pushSubscription);
                    return pushSubscription;
                });
        }

        function subscribeUser() {
            if (!('serviceWorker' in navigator)) {
                window.console.error("ServiceWorkers Not supporter here");
                return;
            }
            if(!('PushManager' in window)){
                window.console.error("PushManager Not supporter here");
                return;
            }
            window.console.debug("All is looking well thus far.");

            registerServiceWorker();

            askForPermission();

            subscribeUserToPush();
        }

        //TODO: I still need to figure out what is the CORRECT workflow
        //subscribeUser();
    </script>
</body>
</html>