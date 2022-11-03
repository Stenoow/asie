import * as BufferGeometryUtils from './libraries/BufferGeometryUtils.js';

// this function is more perf scene because he change all geometries in one !
export function createAttributeColor(geometry, r, g, b)
{
    const colors = [];
    for(let j = 0; j < geometry.attributes.position.count; j++)
    {
        colors.push( r / 255, g / 255, b / 255 );
    }
    geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
    if(geometry.index !== null)
        return geometry.toNonIndexed();
    else
        return geometry;
}

export function createMergedGeometry( geometries, material, castShadow = false, receiveShadow = false)
{
    let mergedGeometries = mergeGeometries( geometries )
    let mesh = new THREE.Mesh(mergedGeometries, material);
    mesh.castShadow = castShadow;
    mesh.receiveShadow = receiveShadow;
    return mesh;
}

export function mergeGeometries( geometries )
{
    let mergedGeometries = BufferGeometryUtils.mergeBufferGeometries(geometries);
    mergedGeometries.computeVertexNormals();
    return mergedGeometries;
}

export function degreesToRadians(degrees)
{
    let pi = Math.PI;
    return degrees * (pi/180);
}

export function onWindowResize(camera, renderer, scene)
{
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();

    renderer.setSize(window.innerWidth / 100 * 80, window.innerHeight / 100 * 80);
    renderer.render(scene, camera);
}