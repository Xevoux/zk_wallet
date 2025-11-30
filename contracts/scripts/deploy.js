const hre = require("hardhat");

async function main() {
  console.log("Starting deployment...");
  
  const [deployer] = await hre.ethers.getSigners();
  console.log("Deploying contracts with account:", deployer.address);
  
  const balance = await hre.ethers.provider.getBalance(deployer.address);
  console.log("Account balance:", hre.ethers.formatEther(balance), "MATIC");
  
  // Deploy Groth16Verifier
  console.log("\n1. Deploying Groth16Verifier...");
  const Groth16Verifier = await hre.ethers.getContractFactory("Groth16Verifier");
  const verifier = await Groth16Verifier.deploy();
  await verifier.waitForDeployment();
  const verifierAddress = await verifier.getAddress();
  console.log("✓ Groth16Verifier deployed to:", verifierAddress);
  
  // Deploy ZKPayment
  console.log("\n2. Deploying ZKPayment...");
  const ZKPayment = await hre.ethers.getContractFactory("ZKPayment");
  const zkPayment = await ZKPayment.deploy(verifierAddress);
  await zkPayment.waitForDeployment();
  const zkPaymentAddress = await zkPayment.getAddress();
  console.log("✓ ZKPayment deployed to:", zkPaymentAddress);
  
  // Verify deployment
  console.log("\n3. Verifying deployment...");
  const contractBalance = await hre.ethers.provider.getBalance(zkPaymentAddress);
  console.log("Contract balance:", hre.ethers.formatEther(contractBalance), "MATIC");
  
  const owner = await zkPayment.owner();
  console.log("Contract owner:", owner);
  
  const verifierInContract = await zkPayment.verifier();
  console.log("Verifier address in contract:", verifierInContract);
  
  // Save deployment info
  const fs = require('fs');
  const deploymentInfo = {
    network: hre.network.name,
    chainId: (await hre.ethers.provider.getNetwork()).chainId.toString(),
    deployer: deployer.address,
    timestamp: new Date().toISOString(),
    contracts: {
      Groth16Verifier: verifierAddress,
      ZKPayment: zkPaymentAddress
    }
  };
  
  const outputPath = `./deployments/${hre.network.name}.json`;
  fs.mkdirSync('./deployments', { recursive: true });
  fs.writeFileSync(outputPath, JSON.stringify(deploymentInfo, null, 2));
  
  console.log("\n✓ Deployment complete!");
  console.log("Deployment info saved to:", outputPath);
  console.log("\n=== DEPLOYMENT SUMMARY ===");
  console.log("Network:", hre.network.name);
  console.log("Chain ID:", deploymentInfo.chainId);
  console.log("Groth16Verifier:", verifierAddress);
  console.log("ZKPayment:", zkPaymentAddress);
  console.log("==========================\n");
  
  // Instructions for verification on Polygonscan
  if (hre.network.name === "amoy" || hre.network.name === "mumbai" || hre.network.name === "polygon") {
    console.log("To verify contracts on Polygonscan, run:");
    console.log(`npx hardhat verify --network ${hre.network.name} ${verifierAddress}`);
    console.log(`npx hardhat verify --network ${hre.network.name} ${zkPaymentAddress} ${verifierAddress}`);
  }
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error(error);
    process.exit(1);
  });
