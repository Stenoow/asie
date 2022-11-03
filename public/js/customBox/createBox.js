import * as Utils from "../utils";

export default function createBox(){
    let box = [];
    let bottomBox = new THREE.BoxGeometry(10, 1, 10);
    bottomBox = Utils.createAttributeColor(bottomBox, 20, 20, 20);
    box.push(bottomBox);

    let boxWall = new THREE.BoxGeometry(10.4, 3, 0.2);
    for(let i = 0; i < 4; i++){
        let boxClone = boxWall.clone();
        boxClone = Utils.createAttributeColor(boxClone, 60, 60, 60);
        boxClone.translate(0, 1, -5.1);
        boxClone.rotateY(Utils.degreesToRadians(i *90));
        box.push(boxClone);
    }

    let mesh = Utils.createMergedGeometry(box, new THREE.MeshLambertMaterial({
        vertexColors: THREE.VertexColors,
        side: THREE.DoubleSide
    }));
    // set name for with a click on this he don't remove
    mesh.name = 'box';
    return mesh;
}