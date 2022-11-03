import * as Utils from "../utils";

export default function createMaki(){
    let maki = [];
    let rice = new THREE.CylinderGeometry(1, 1, 1.5);
    rice = Utils.createAttributeColor(rice, 200, 200, 200);
    maki.push(rice);
    let nori = new THREE.CylinderGeometry(1.1, 1.1, 1.4);
    nori = Utils.createAttributeColor(nori, 20, 60, 20);
    maki.push(nori);
    let salmon = new THREE.CylinderGeometry(0.5, 0.5, 1.6);
    salmon = Utils.createAttributeColor(salmon, 255, 160, 122);
    maki.push(salmon);
    let avocado = new THREE.BoxGeometry(0.5, 1.7, 0.7);
    avocado = Utils.createAttributeColor(avocado, 86, 130, 3);
    avocado.translate(0.3, 0, 0);
    maki.push(avocado);

    let mesh = Utils.createMergedGeometry(maki, new THREE.MeshLambertMaterial({
        vertexColors: THREE.VertexColors,
        side: THREE.DoubleSide
    }));
    mesh.name = 'maki';
    mesh.productId = 1;
    return mesh;
}