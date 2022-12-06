window.addEventListener('load', (event) => {
    const fileSelectorInput = document.getElementById('file_upload');
    const reader = new FileReader();
    const markerIcon = {
        url: templateUrl + "/assets/img/marker.svg",
        scaledSize: new google.maps.Size(20, 20)
    };


    initFileSelector();

    function initFileSelector(){
        fileSelectorInput.addEventListener('change', (e) => {
            e.preventDefault()
            console.log('fff')
    
            const csvFile = e.target.files;
            const formData = new FormData();

            if(csvFile[0].type !== "text/csv"){
                throw new Error("File needs to be a CSV");
            }
            try {
                formData.append( 'csvfile', csvFile[0], csvFile[0].name );
            } catch (e) {
                console.error(e);
            }
    
            reader.readAsText(csvFile[0]);

            reader.onload = function (e) {
                const text = e.target.result;
                postData(formData, text, csvFile[0])
            };
        });

        let url = new URL(window.location.href);

         if (url.searchParams.has('v')) {
            // console.log(url.searchParams.get('v') )
            let queryString = url.searchParams.get('v');
            loadSubmissionMarkers(queryString)
        }

    }

    function csvToJSON(csv){
        let lines=csv.split("\n");
        let result = [];
        let headers=lines[0].split(",");
      
        for(let i=1; i<lines.length; i++){
            let obj = {};
            let currentline=lines[i].split(",");
      
            for(let j=0;j<headers.length;j++){
                obj[headers[j]] = currentline[j];
            }

            result.push(obj);
        }
        return(result);
    }

    function postData(formData, text, file){
        let jsonCSV = csvToJSON(text)

        // csv to map
        mapMarkers(jsonCSV)
        postToBackend(formData, file)
    }

    function postToBackend(formData, file){
        // csv to backend
        formData.append( 'action', 'send_formcd' );
        formData.append( 'nonce', wp_pageviews_ajax.nonce );
    
        console.log(formData)

        // Post formData using the Fetch API
        fetch(wp_pageviews_ajax.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            createShareableLink(data)
          })
        .catch((error) => {
            console.error(error);
        });
    }

    function createShareableLink(data){
        const shareable_link = document.getElementById('shareable_link');
        const shareIcon = '<span class="ml-5 w-[20px] inline-flex"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M502.6 70.63l-61.25-61.25C435.4 3.371 427.2 0 418.7 0H255.1c-35.35 0-64 28.66-64 64l.0195 256C192 355.4 220.7 384 256 384h192c35.2 0 64-28.8 64-64V93.25C512 84.77 508.6 76.63 502.6 70.63zM464 320c0 8.836-7.164 16-16 16H255.1c-8.838 0-16-7.164-16-16L239.1 64.13c0-8.836 7.164-16 16-16h128L384 96c0 17.67 14.33 32 32 32h47.1V320zM272 448c0 8.836-7.164 16-16 16H63.1c-8.838 0-16-7.164-16-16L47.98 192.1c0-8.836 7.164-16 16-16H160V128H63.99c-35.35 0-64 28.65-64 64l.0098 256C.002 483.3 28.66 512 64 512h192c35.2 0 64-28.8 64-64v-32h-47.1L272 448z"/></svg></span>';


        shareable_link.insertAdjacentHTML(
            'beforeend',
            '<span class="bg-[#000]/25 inline-flex items-center p-4 rounded-lg text-white mb-5 flex-col md:flex-row"><b class="mr-2">Nice airports!</b> Your shareable url is: <a class="ml-2 bg-white p-2 rounded-lg text-black hover:bg-[#eee]/25 focus:bg-green-500 transition-colors  duration-500" href="'+window.location.href+'?v='+data+'">'+window.location.href+'?v='+data+shareIcon+'</span>',
        );

        copyToClipBoard(shareable_link)
    }

    function copyToClipBoard(shareable_link){

        shareable_link.addEventListener('click', function(e){
            e.preventDefault()
        //   console.log('I am now applied to the element'); 

            // navigator.clipboard.writeText(e.target.getAttribute('href')).then(() => {
            // /* clipboard successfully set */
            // }, () => {
            // /* clipboard write failed */
            // });

            copyToClipboard(e.target.getAttribute('href'))
            .then(() => console.log('text copied !'))
            .catch(() => console.log('error'));

        });
    }

    // return a promise
    function copyToClipboard(textToCopy) {
        // navigator clipboard api needs a secure context (https)
        if (navigator.clipboard && window.isSecureContext) {
            // navigator clipboard api method'
            return navigator.clipboard.writeText(textToCopy);
        } else {
            // text area method
            let textArea = document.createElement("textarea");
            textArea.value = textToCopy;
            // make the textarea out of viewport
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            return new Promise((res, rej) => {
                // here the magic happens
                document.execCommand('copy') ? res() : rej();
                textArea.remove();
            });
        }
    }


    // new markers
    function addMarker(location,objectCsv) {
        
        marker = new google.maps.Marker({
            position: location,
            map: map,
            icon: markerIcon
        });

        let contentString =
        '<div id="content">' +
            '<h2 class="font-bold">'+objectCsv['Airport Name']+'</h2>' +
            '<div id="bodyContent">' +
                "<p>Altitude: "+objectCsv.Altitude +"</p>" +
                "<p>City: "+objectCsv.City +"</p>" +
                "<p>Country: "+objectCsv.Country +"</p>" +
                "<p>Latitude & Longitude: "+objectCsv.Latitude+", "+objectCsv.Longitude +"</p>" +
            "</div>" +
        "</div>";


        marker['infowindow'] = new google.maps.InfoWindow({
            content: contentString
        });

        google.maps.event.addListener(marker, 'click', function () {
            this['infowindow'].open(map, this);
        });

    }

    function hideOverlay(){
          let overlay = document.getElementById('map_overlay');
          overlay.classList.add('opacity-0');
          overlay.classList.add('invisible');
    }

    function mapMarkers(csvData) {
        hideOverlay()
        csvData.map((elem, index)=>{
            coordinates = new google.maps.LatLng(elem.Latitude, elem.Longitude);
            addMarker(coordinates, elem);
        })
    }

    function loadSubmissionMarkers(queryString){
        const getSubmission = new FormData();
        getSubmission.append( 'action', 'get_submission' );
        getSubmission.append( 'nonce', wp_pageviews_ajax.nonce );
        getSubmission.append( 'submission_id', queryString );

        fetch(wp_pageviews_ajax.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: getSubmission
        })
        .then(response => response.json())
        .then(data => {
            // console.log(data)
            fetchCSVURL(data)
        })
        .catch((error) => {
            console.error(error);
        });
    }

    function fetchCSVURL(data){
        const url = data.url;
        fetch(url)
        .then( response => response.text() )
        .then( data =>  displayCSVFromUrl(data) )
        .catch((error) => {
            console.error(error);
        });
       
    }
    function displayCSVFromUrl(data){
        let jsonCSV = csvToJSON(data)
        mapMarkers(jsonCSV)
    }

});