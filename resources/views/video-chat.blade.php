<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Video Chat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold text-center mb-6">Live Video Chat</h1>
        <div class="flex justify-center gap-4">
            <video id="localVideo" autoplay muted class="border rounded-lg"></video>
            <video id="remoteVideo" autoplay class="border rounded-lg"></video>
        </div>
        <div class="flex justify-center mt-6">
            <button id="startCall" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                Start Call
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/laravel-echo"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.5.3/socket.io.js"></script>
    <script>
        const startCallButton = document.getElementById('startCall');
        const localVideo = document.getElementById('localVideo');
        const remoteVideo = document.getElementById('remoteVideo');

        // WebRTC Setup
        let localStream;
        let peerConnection;

        const config = {
            iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
        };

        async function startCall() {
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            localVideo.srcObject = localStream;

            peerConnection = new RTCPeerConnection(config);

            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });

            peerConnection.ontrack = (event) => {
                remoteVideo.srcObject = event.streams[0];
            };

            peerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    // Send candidate to the other user via WebSocket
                    window.Echo.channel('video-signaling')
                        .whisper('ice-candidate', { candidate: event.candidate });
                }
            };

            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            // Send offer to the other user
            window.Echo.channel('video-signaling')
                .whisper('offer', { offer });
        }

        startCallButton.addEventListener('click', startCall);

        // Laravel Echo for signaling
        window.Echo = new Echo({
            broadcaster: 'reverb',
            host: window.location.hostname + ':6001',
        });

        window.Echo.channel('video-signaling')
            .listenForWhisper('offer', async (data) => {
                await peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer));
                const answer = await peerConnection.createAnswer();
                await peerConnection.setLocalDescription(answer);

                window.Echo.channel('video-signaling')
                    .whisper('answer', { answer });
            })
            .listenForWhisper('answer', async (data) => {
                await peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
            })
            .listenForWhisper('ice-candidate', (data) => {
                peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
            });
    </script>
</body>
</html>
