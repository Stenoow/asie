import * as Utils from "../utils";

export default function createNigiriSalmon(){
    let nigiriSalmon = [];
    let rice = new THREE.BoxGeometry(1.5, 1, 1);
    rice = Utils.createAttributeColor(rice, 200, 200, 200);
    nigiriSalmon.push(rice);

    const salmonShape = new THREE.Shape()
        .moveTo(-5, -1)
        .lineTo(-5, 0)
        .lineTo(-4, 0.2)
        .lineTo(-3, 0.4)
        .lineTo(-2, 0.6)
        .lineTo(-1, 0.8)
        .lineTo(0, 1)
        .lineTo(1, 0.8)
        .lineTo(2, 0.6)
        .lineTo(3, 0.4)
        .lineTo(4, 0.2)
        .lineTo(5, 0)
        .lineTo(5, -1)
        .lineTo(0, 0)
        .lineTo(-5, -1);

    const salmonShapeSettings = {
        depth: 0.5,
        bevelSize: 1,
    };
    let salmonShapeGeometry = new THREE.ExtrudeGeometry(salmonShape, salmonShapeSettings);
    salmonShapeGeometry.scale(0.2, 0.2, 0.1)

    let salmon = salmonShapeGeometry.clone();
    salmon = Utils.createAttributeColor(salmon, 255, 120, 60);
    salmon.translate(0, 0.4, 0);
    nigiriSalmon.push(salmon);

    let mesh = Utils.createMergedGeometry(nigiriSalmon, new THREE.MeshLambertMaterial({
        vertexColors: THREE.VertexColors,
        side: THREE.DoubleSide
    }));
    mesh.rotateY(Utils.degreesToRadians(45));
    mesh.name = 'nigiri';
    mesh.productId = 7;
    return mesh;
}