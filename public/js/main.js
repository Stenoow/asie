import { OrbitControls } from 'https://cdn.jsdelivr.net/npm/three@0.121.1/examples/jsm/controls/OrbitControls.js';
import createBox from "./customBox/createBox.js";
import * as Utils from "./utils.js";
import AssemblyBox from "./AssemblyBox";

let camera, scene, renderer;

init();

function init() {
    // set the camera of scene
    camera = new THREE.PerspectiveCamera(
        60,
        (window.innerWidth / 100 * 80) / (window.innerHeight / 100 * 80),
        0.1,
        2500);

    camera.position.x = 10;
    camera.position.y = 20;
    camera.position.z = 10;
    camera.lookAt(new THREE.Vector3(0, 0, 0));

    scene = new THREE.Scene();
    scene.background = new THREE.Color( 0xbfe3dd );

    // ========== MAIN CODE ==============

    // create objects in 3D
    let box = createBox();
    scene.add(box);

    // ======== Light ================

    const light = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(light);

    let pl = new THREE.PointLight(0xffffff, 0.5, 10000);
    pl.position.set( -100, 1000, 1000);
    pl.castShadow = true;
    scene.add(pl);

    // ========== RENDERER ============

    renderer = new THREE.WebGLRenderer({antialias :true});
    renderer.setPixelRatio( window.devicePixelRatio );
    renderer.setSize( window.innerWidth / 100 * 80, window.innerHeight / 100 * 80 );
    renderer.autoClear = true;

    //====== ORBIT CONTROL ============

    // let controls = new OrbitControls(camera, renderer.domElement);
    // controls.enabled = false;

    // if the client resize the window the canvas resize too
    window.addEventListener( 'resize', () => Utils.onWindowResize(camera, renderer, scene));

    let assemblyBox = new AssemblyBox(scene);
    assemblyBox.initiateEventClick(renderer, scene, camera);

    // add the canvas of three js in html
    var container = document.getElementById( 'ThreeJS' );
    container.appendChild( renderer.domElement );
    render();
}

function render()
{
    requestAnimationFrame(render);
    renderer.render(scene, camera);
}